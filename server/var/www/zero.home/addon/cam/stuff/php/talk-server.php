<?php

declare(strict_types=1);

use Cam\Config;
use Cam\DvripTalkSession;
use Cam\StreamDvripTransport;
use Cam\TalkAudioConverter;
use Cam\TalkSessionRegistry;
use Cam\TalkToken;
use Cam\TalkWebSocket;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SocketServer;

require_once '/srv/alina/vendor/autoload.php';
foreach ([
    'Config.php', 'DvripException.php', 'DvripTransport.php', 'DvripDuplexTransport.php',
    'StreamDvripTransport.php', 'DvripPacketCodec.php', 'DvripReader.php', 'DvripClient.php',
    'DvripTalkPacketCodec.php', 'DvripTalkSession.php', 'TalkAudioConverter.php',
    'TalkSessionRegistry.php', 'TalkToken.php', 'TalkWebSocket.php',
] as $file) require_once __DIR__ . '/' . $file;

$config = Config::load(dirname(__DIR__, 2) . '/config.php');
$loop = Factory::create();
$handler = new TalkWebSocket(
    $config,
    TalkToken::fromEnvironment(),
    new TalkSessionRegistry(),
    static function (string $cameraId, array $camera): DvripTalkSession {
        $dvrip = $camera['dvrip'];
        $transport = static fn (): StreamDvripTransport => new StreamDvripTransport(
            $camera['host'],
            $dvrip['port'],
            (float) ($dvrip['timeout'] ?? 5.0),
        );
        return new DvripTalkSession($transport(), $transport());
    },
    static fn (): TalkAudioConverter => new TalkAudioConverter(),
    $loop,
);
$webSocket = new WsServer($handler);
$webSocket->enableKeepAlive($loop, 20);
$socket = new SocketServer('0.0.0.0:8081', [], $loop);
$server = new IoServer(new HttpServer($webSocket), $socket, $loop);
fwrite(STDOUT, "Camera talk WebSocket listening on 0.0.0.0:8081\n");
$server->run();
