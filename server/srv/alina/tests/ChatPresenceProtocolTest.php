<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

$storage = new MemoryChatStorage();
$origin = 'https://zero.home:50443';
$general = $storage->openChat($origin, 'general');
$storage->appendMessage($general['id'], [
    'senderType' => 'user', 'senderId' => '22', 'senderName' => 'Codex', 'senderEmblem' => '/codex.png',
    'text' => 'Earlier message', 'createdAt' => 100, 'clientMessageId' => 'presence-seed-22',
]);

$server = new ChatServer($storage, new StreamSelectLoop());
$codex = new ChatTestClient(1, $origin);
$alice = new ChatTestClient(2, $origin);
$server->onOpen($codex);
$server->onOpen($alice);
chatSend($server, $alice, 'join', ['CurrentUser' => ['id' => 17, 'nickname' => 'Alice']]);
$people = array_column($alice->lastEvent('presence')['participants'] ?? [], null, 'id');
chatExpectSame(false, $people['user:22']['online'] ?? null, 'retained participant starts offline');

chatSend($server, $codex, 'join', ['channel' => 'other', 'CurrentUser' => ['id' => 22, 'nickname' => 'Codex', 'emblem' => '/codex.png']]);

$presence = $alice->lastEvent('presence');
$people = array_column($presence['participants'] ?? [], null, 'id');
chatExpectSame(true, $people['user:22']['online'] ?? null, 'participant connected to another room is online');
chatExpectSame(false, $people['user:22']['inChat'] ?? null, 'participant connected elsewhere is not in the current chat');
chatExpectSame(false, $people['user:22']['typing'] ?? null, 'participant connected elsewhere is not typing in this chat');

chatSend($server, $codex, 'join', ['channel' => 'general', 'CurrentUser' => ['id' => 22, 'nickname' => 'Codex', 'emblem' => '/codex.png']]);
$people = array_column($alice->lastEvent('presence')['participants'] ?? [], null, 'id');
chatExpectSame(true, $people['user:22']['inChat'] ?? null, 'opening the current room changes retained participant to in-chat');

chatSend($server, $codex, 'typing', ['channel' => 'general', 'typing' => true]);
$people = array_column($alice->lastEvent('presence')['participants'] ?? [], null, 'id');
chatExpectSame(true, $people['user:22']['typing'] ?? null, 'typing is reported only for a live current-room participant');

echo "PASS: retained participants distinguish origin presence, room presence and typing\n";
