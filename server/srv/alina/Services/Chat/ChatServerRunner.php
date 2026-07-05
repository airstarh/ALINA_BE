<?php

namespace alina\Services\Chat;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

require_once __DIR__ . '../../vendor/autoload.php';

$chatHandler = new ChatServer();
$webSocket   = new WsServer($chatHandler);
$httpServer  = new HttpServer($webSocket);
$socket      = new \React\Socket\SocketServer('0.0.0.0:8080'); // Порт 8080
$server      = new IoServer($httpServer, $socket);

echo "WebSocket сервер слушает порт 8080...\n";
$server->run();
