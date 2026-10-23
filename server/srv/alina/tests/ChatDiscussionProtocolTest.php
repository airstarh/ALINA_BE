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
$directory = new ChatTestClient(2);
$server->onOpen($directory);
$server->onMessage($directory, json_encode([
    'version' => 2, 'type' => 'list_rooms', 'CurrentUser' => ['id' => 17],
    'pagination' => ['mine' => ['limit' => 1000, 'offset' => 0], 'others' => ['limit' => 1000, 'offset' => 0]],
], JSON_THROW_ON_ERROR));
$roomSnapshotsBefore = count($directory->events('rooms'));
chatSend($server, $alice, 'discuss_message', ['messageId' => $source['id']]);
$event = $alice->lastEvent('discussion_ready');
chatExpectSame($source['id'], $event['sourceMessageId'] ?? null, 'discussion event must identify source');
chatExpectSame('discussion_' . $source['id'], $event['discussion']['channel'] ?? null, 'discussion must return child channel');
chatExpectSame(0, $event['discussion']['responseCount'] ?? null, 'new discussion has zero responses');
$roomSnapshots = $directory->events('rooms');
chatExpectSame($roomSnapshotsBefore + 1, count($roomSnapshots), 'discussion creation must refresh connected room directories');
$latestRooms = $roomSnapshots[array_key_last($roomSnapshots)];
$visibleChannels = [...array_column($latestRooms['mine']['rows'], 'channel'), ...array_column($latestRooms['others']['rows'], 'channel')];
chatExpect(in_array('discussion_' . $source['id'], $visibleChannels, true), 'new discussion must appear in the live room directory');
echo "PASS: socket creates and returns message discussion\n";
