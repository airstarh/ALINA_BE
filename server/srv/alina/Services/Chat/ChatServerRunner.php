<?php

namespace alina\Services\Chat;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

require_once __DIR__ . '../../vendor/autoload.php';

class ChatServerRunner
{
    private $pidFile = '/var/log/pid';

    public function run()
    {
        $chatHandler = new ChatServer();
        $webSocket   = new WsServer($chatHandler);
        $httpServer  = new HttpServer($webSocket);
        $socket      = new \React\Socket\SocketServer('0.0.0.0:8080');
        $server      = new IoServer($httpServer, $socket);

        echo "WebSocket сервер слушает порт 8080...\n";
        $server->run();
    }

    public function isPortInUse(?string $host, ?int $port, ?float $timeout = 1.0): bool
    {
        $host    = $host    ?? '127.0.0.1';
        $port    = $port    ?? 8080;
        $timeout = $timeout ?? 1.0;
        $sock    = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($sock === false) {
            return false;
        }
        fclose($sock);

        return true;
    }

    public function checkPid()
    {
        $pidFile = $this->pidFile;

        if (file_exists($pidFile)) {
            $existingPid = (int) $this->getSocketState();

            if ($existingPid > 0 && posix_kill($existingPid, 0)) {
                // Signal 0 just checks if the process exists; doesn't send anything
                echo "Another instance is already running (PID: {$existingPid}). Exiting.\n";
                exit(1);
            }
            // Stale PID file: process died, remove it
            unlink($pidFile);
        }

        // Write our PID
        $this->setSocketState((string) getmypid());

        register_shutdown_function(static fn () => @unlink($pidFile));

        echo "Started with PID " . getmypid() . "\n";
        // Continue with server startup...
    }

    public function getSocketState()
    {
        return (int) file_get_contents($this->pidFile);
    }

    public function setSocketState(int $value)
    {
        file_put_contents($this->pidFile, $value);

        return $this->getSocketState();
    }
}
