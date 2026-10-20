<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';
use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

$storage = new MemoryChatStorage();
$server = new ChatServer($storage, new StreamSelectLoop());
$alice = new ChatTestClient(1);
$server->onOpen($alice);
chatSend($server, $alice, 'join');
chatSend($server, $alice, 'message', ['msg' => 'Discuss this', 'clientMessageId' => 'source']);
$source = $alice->lastEvent('message')['message'];
chatSend($server, $alice, 'discuss_message', ['messageId' => $source['id']]);
$event = $alice->lastEvent('discussion_ready');
chatExpectSame($source['id'], $event['sourceMessageId'] ?? null, 'discussion event must identify source');
chatExpectSame('discussion_' . $source['id'], $event['discussion']['channel'] ?? null, 'discussion must return child channel');
chatExpectSame(0, $event['discussion']['responseCount'] ?? null, 'new discussion has zero responses');
echo "PASS: socket creates and returns message discussion\n";
