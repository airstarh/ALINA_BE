<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

$storage = new MemoryChatStorage();
$other = $storage->openChat('https://zero.home:50443', 'other', 'Other room');
$storage->appendMessage($other['id'], [
    'senderType' => 'user', 'senderId' => '22', 'senderName' => 'Bob', 'senderEmblem' => '',
    'text' => 'hello', 'createdAt' => 100, 'clientMessageId' => 'other-1',
]);
$server = new ChatServer($storage, new StreamSelectLoop());
$directory = new ChatTestClient(1);
$server->onOpen($directory);
chatSend($server, $directory, 'list_rooms', [
    'CurrentUser' => ['id' => 17, 'nickname' => 'Alice'],
    'pagination' => ['mine' => ['limit' => 1000, 'offset' => 0], 'others' => ['limit' => 1000, 'offset' => 0]],
]);
chatSend($server, $directory, 'toggle_chat_pin', ['channel' => 'other']);
$rooms = $directory->lastEvent('rooms');
chatExpectSame(['other'], array_column($rooms['mine']['rows'] ?? [], 'channel'), 'a pinned foreign room must move into My chats');
chatExpectSame(true, $rooms['mine']['rows'][0]['isPinned'] ?? null, 'a pinned room must expose its state');

$chat = new ChatTestClient(2);
$server->onOpen($chat);
chatSend($server, $chat, 'join', ['channel' => 'other', 'CurrentUser' => ['id' => 17, 'nickname' => 'Alice']]);
chatSend($server, $chat, 'message', ['channel' => 'other', 'msg' => 'pin me', 'clientMessageId' => 'pin-me']);
$message = $chat->lastEvent('message')['message'];
chatSend($server, $chat, 'toggle_message_pin', ['channel' => 'other', 'messageId' => $message['id']]);
$pin = $chat->lastEvent('message_pin_changed');
chatExpectSame($message['id'], $pin['message']['id'] ?? null, 'pin event must contain the pinned message');
chatExpectSame(true, $pin['message']['isPinned'] ?? null, 'pinned message must expose its state');
chatSend($server, $chat, 'delete_message', ['channel' => 'other', 'messageId' => $message['id']]);
chatExpect(str_contains($chat->lastEvent('error')['error'] ?? '', 'pinned'), 'pinned message must not be deletable');
chatSend($server, $chat, 'message', ['channel' => 'other', 'msg' => 'second pin', 'clientMessageId' => 'second-pin']);
$secondMessage = $chat->lastEvent('message')['message'];
chatSend($server, $chat, 'toggle_message_pin', ['channel' => 'other', 'messageId' => $secondMessage['id']]);
chatExpectSame($secondMessage['id'], $chat->lastEvent('message_pin_changed')['message']['id'] ?? null, 'pinning a second message must replace the first pin');
chatSend($server, $chat, 'toggle_message_pin', ['channel' => 'other', 'messageId' => $secondMessage['id']]);
chatExpectSame(true, array_key_exists('message', $chat->lastEvent('message_pin_changed') ?? []), 'pin clear event must include a nullable message field');
chatExpectSame(null, $chat->lastEvent('message_pin_changed')['message'], 'clicking the current pin must clear it');
$activity = $storage->appendMessage($other['id'], [
    'senderType' => 'user_service', 'senderId' => '17', 'senderName' => 'Alice', 'senderEmblem' => '',
    'text' => 'PRESENCE:activity', 'createdAt' => 101, 'clientMessageId' => 'activity-pin',
])['message'];
chatSend($server, $chat, 'toggle_message_pin', ['channel' => 'other', 'messageId' => $activity['id']]);
chatExpect(str_contains($chat->lastEvent('error')['error'] ?? '', 'activity updates'), 'automated activity must not be pinnable');

$guest = new ChatTestClient(3);
$server->onOpen($guest);
chatSend($server, $guest, 'join', ['channel' => 'other', 'CurrentUser' => ['guestId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']]);
chatSend($server, $guest, 'toggle_message_pin', ['channel' => 'other', 'messageId' => $message['id']]);
chatExpect(str_contains($guest->lastEvent('error')['error'] ?? '', 'registered'), 'guests must not pin messages');

echo "PASS: registered users pin chats and one protected message\n";
