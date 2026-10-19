<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

function sendRoomList(ChatServer $server, ChatTestClient $client, array $extra = []): void
{
    $server->onMessage($client, json_encode(array_replace_recursive([
        'version' => 2,
        'type' => 'list_rooms',
        'CurrentUser' => ['id' => 17, 'guestId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'],
        'pagination' => [
            'mine' => ['limit' => 1000, 'offset' => 0],
            'others' => ['limit' => 1000, 'offset' => 0],
        ],
    ], $extra), JSON_THROW_ON_ERROR));
}

$storage = new MemoryChatStorage();
$mine = $storage->openChat('https://zero.home:50443', 'mine');
$storage->appendMessage($mine['id'], [
    'senderType' => 'user', 'senderId' => '17', 'senderName' => 'Alice', 'senderEmblem' => '',
    'text' => 'hello', 'createdAt' => 100, 'clientMessageId' => 'mine-1',
]);
$storage->openChat('https://zero.home:50443', 'other');
$storage->openChat('https://vov.home:50443', 'foreign');

$server = new ChatServer($storage, new StreamSelectLoop());
$client = new ChatTestClient(1, 'https://ZERO.HOME:50443');
$server->onOpen($client);
$chatCountBefore = $storage->chatCount();
sendRoomList($server, $client, ['pagination' => ['mine' => ['limit' => 5000, 'offset' => 0]]]);
$rooms = $client->lastEvent('rooms');
chatExpect(is_array($rooms), 'list_rooms must work before joining');
chatExpectSame(2, $rooms['version'], 'directory response must use protocol version 2');
chatExpectSame(['mine'], array_column($rooms['mine']['rows'], 'channel'), 'registered ID must override stale guest ID');
chatExpectSame(['other'], array_column($rooms['others']['rows'], 'channel'), 'directory must use normalized handshake origin');
chatExpectSame(1000, $rooms['mine']['limit'], 'limit must be clamped to 1000');
chatExpectSame($chatCountBefore, $storage->chatCount(), 'listing must not create or join a room');

$guestClient = new ChatTestClient(2);
$server->onOpen($guestClient);
sendRoomList($server, $guestClient, [
    'CurrentUser' => ['id' => null, 'guestId' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'],
]);
chatExpect($guestClient->lastEvent('rooms') !== null, 'valid persistent guest UUID must be accepted');

$invalidGuest = new ChatTestClient(3);
$server->onOpen($invalidGuest);
sendRoomList($server, $invalidGuest, ['CurrentUser' => ['id' => null, 'guestId' => 'temporary']]);
chatExpect(str_contains($invalidGuest->lastEvent('error')['error'] ?? '', 'identity'), 'invalid guest identity must be rejected');

$badPagination = new ChatTestClient(4);
$server->onOpen($badPagination);
sendRoomList($server, $badPagination, ['pagination' => ['others' => ['limit' => 10, 'offset' => -1]]]);
chatExpect(str_contains($badPagination->lastEvent('error')['error'] ?? '', 'pagination'), 'negative offset must be rejected');

$storage->nextError = new RuntimeException('temporary');
sendRoomList($server, $client);
chatExpect(str_contains($client->lastEvent('error')['error'] ?? '', 'store'), 'storage failure must become an error event');
sendRoomList($server, $client);
chatExpect($client->lastEvent('rooms') !== null, 'socket must remain usable after storage failure');

$missingOrigin = new ChatTestClient(5, null);
$server->onOpen($missingOrigin);
sendRoomList($server, $missingOrigin);
chatExpect(str_contains($missingOrigin->lastEvent('error')['error'] ?? '', 'Origin'), 'directory requires normalized HTTP origin');

echo "PASS: pre-join room directory protocol validates identity, origin and pagination\n";
