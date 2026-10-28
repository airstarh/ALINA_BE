<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface as SocketConnection;
use React\Socket\SocketServer;
use Throwable;

final class FileJobServerRunner
{
    public static function go(string $host='0.0.0.0',int $webSocketPort=8082,int $wakePort=8083): never
    {
        $loop=Factory::create();
        $storage=new DatabaseFileJobStorage();
        $socketHandler=new FileJobServer($storage);
        $processor=new VideoJobProcessor(new VideoTranscoder(),$loop);
        $finalizer=new FileJobFinalizer();
        $worker=new FileJobWorker($storage,$processor,$loop,fn(string $id)=>$socketHandler->publishJob($id),fn(object $job)=>$finalizer->finalize($job));

        $webSocket=new WsServer($socketHandler);
        $webSocket->enableKeepAlive($loop,20);
        $publicSocket=new SocketServer("{$host}:{$webSocketPort}",[],$loop);
        new IoServer(new HttpServer($webSocket),$publicSocket,$loop);

        $wakeSocket=new SocketServer("{$host}:{$wakePort}",[],$loop);
        $wakeSocket->on('connection',static function(SocketConnection $connection) use($worker):void {
            $buffer='';
            $connection->on('data',static function(string $chunk) use($connection,$worker,&$buffer):void {
                $buffer.=$chunk;
                if (strlen($buffer)>4096) { $connection->end("{\"ok\":false}\n"); return; }
                $newline=strpos($buffer,"\n");
                if ($newline===false) return;
                $frame=substr($buffer,0,$newline+1);
                try { $request=WakeRequest::parse($frame); $ok=$worker->wake($request->publicId,$request->secret); $connection->end(json_encode(['ok'=>$ok])."\n"); }
                catch(Throwable) { $connection->end("{\"ok\":false}\n"); }
            });
        });

        $worker->enableRecoveryTimer(30);
        $loop->futureTick(fn()=>$worker->recover());
        echo "File job socket listening on {$host}:{$webSocketPort}; wake socket {$host}:{$wakePort}\n";
        $loop->run();
        exit(0);
    }
}
