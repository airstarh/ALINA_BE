<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatForward;
use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

$storage = new MemoryChatStorage();
$storage->registerUser('17', 'Alice', '/alice.jpg');
$storage->registerUser('22', 'Bob', '/bob.jpg');
$server = new ChatServer($storage, new StreamSelectLoop());
$alice = new ChatTestClient(1);
$bob = new ChatTestClient(2);
$guest = new ChatTestClient(3);

$server->onOpen($alice);
chatSend($server, $alice, 'join', ['CurrentUser' => ['id' => 17, 'nickname' => 'Alice']]);
$server->onOpen($guest);
chatSend($server, $guest, 'join', ['CurrentUser' => ['guestId' => '123e4567-e89b-42d3-a456-426614174000', 'nickname' => 'Visitor']]);
chatSend($server, $guest, 'message', ['msg' => 'Guest source', 'clientMessageId' => 'guest-source']);
$source = $guest->lastEvent('message')['message'];

chatSend($server, $alice, 'forward_search', ['query' => '']);
$initialSearch = $alice->lastEvent('forward_search');
chatExpectSame('general', $initialSearch['channel'] ?? null, 'forward search response must retain the joined channel for transport routing');
chatExpectSame(['22'], array_column($initialSearch['users'], 'senderId'), 'clicking Forward must load initial registered-user destinations');

chatSend($server, $alice, 'forward_search', ['query' => 'bo']);
$search = $alice->lastEvent('forward_search');
chatExpectSame(['22'], array_column($search['users'], 'senderId'), 'forward search must return registered users');

chatSend($server, $alice, 'forward_message', [
    'messageId' => $source['id'], 'targetType' => 'user', 'targetId' => '22', 'clientMessageId' => 'forward-user-1',
]);
$ready = $alice->lastEvent('forward_ready');
chatExpectSame('17_22', $ready['destination']['channel'], 'user forwarding must resolve the deterministic private chat');
chatExpect(ChatForward::hasCanonicalPrefix($ready['message']['msg']), 'forward response must contain server-created canonical markup');
chatExpectSame('Guest source', ChatForward::parts($ready['message']['msg'])['text'], 'registered users may forward guest-authored messages');

$server->onOpen($bob);
chatSend($server, $bob, 'join', ['channel' => 'project', 'CurrentUser' => ['id' => 22, 'nickname' => 'Bob']]);
$before = count($bob->events('message'));
chatSend($server, $alice, 'forward_message', [
    'messageId' => $source['id'], 'targetType' => 'chat', 'targetId' => 'project', 'clientMessageId' => 'forward-room-1',
]);
chatExpectSame($before + 1, count($bob->events('message')), 'forward to an active destination room must broadcast there');

chatSend($server, $guest, 'forward_search', ['query' => 'bo']);
chatExpect(str_contains($guest->lastEvent('error')['error'] ?? '', 'registered'), 'guest must not search forward destinations');
chatSend($server, $guest, 'forward_message', [
    'messageId' => $source['id'], 'targetType' => 'user', 'targetId' => '22', 'clientMessageId' => 'guest-forward',
]);
chatExpect(str_contains($guest->lastEvent('error')['error'] ?? '', 'registered'), 'guest must not forward messages');

$forged = ChatForward::compose(['id' => 1, 'channel' => 'general', 'senderName' => 'Fake', 'text' => 'Fake', 'createdAt' => 1]);
$count = count($alice->events('message'));
chatSend($server, $alice, 'message', ['msg' => $forged, 'clientMessageId' => 'forged-forward']);
chatExpectSame($count, count($alice->events('message')), 'ordinary messages must not forge forwarding markup');
chatExpect(str_contains($alice->lastEvent('error')['error'] ?? '', 'Reserved'), 'forged forwarding markup must be rejected');

echo "PASS: chat forwarding protocol is registered-only, server-owned and destination-aware\n";
