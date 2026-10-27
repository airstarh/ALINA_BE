<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatForward;

$source = [
    'id' => 7, 'channel' => 'source_room', 'senderType' => 'guest', 'senderId' => 'guest-id',
    'senderName' => 'Visitor <One>', 'text' => str_repeat('Ж', 180), 'createdAt' => 1234,
];
$text = ChatForward::compose($source);
chatExpect(ChatForward::hasCanonicalPrefix($text), 'server-composed forward must be canonical');
$parts = ChatForward::parts($text);
chatExpectSame('source_room', $parts['channel'], 'forward must retain source channel');
chatExpectSame(7, $parts['messageId'], 'forward must retain source message ID');
chatExpectSame('Visitor <One>', $parts['author'], 'forward must decode the source author');
chatExpectSame(str_repeat('Ж', 150), $parts['text'], 'forward snapshot must be limited to 150 Unicode characters');

$storage = new MemoryChatStorage();
$storage->registerUser('17', 'Alice', '/alice.jpg');
$storage->registerUser('22', 'Bob', '/bob.jpg');
$storage->registerUser('33', 'Bobby', '/bobby.jpg');
$sourceChat = $storage->openChat('https://zero.home:50443', 'source');
$guest = $storage->appendMessage($sourceChat['id'], [
    'senderType'=>'guest', 'senderId'=>'guest-id', 'senderName'=>'Visitor', 'senderEmblem'=>'',
    'text'=>'Guest message', 'createdAt'=>1000, 'clientMessageId'=>'source-guest',
])['message'];
$storage->openChat('https://zero.home:50443', 'project', 'Project Room');

$directory = $storage->forwardCandidates('https://zero.home:50443', '17', 'bo', 100);
chatExpectSame(['22', '33'], array_column($directory['users'], 'senderId'), 'forward user search must return registered users only');
chatExpectSame([], $directory['rooms'], 'room search must still match by chat name');
$roomDirectory = $storage->forwardCandidates('https://zero.home:50443', '17', 'project', 100);
chatExpectSame(['project'], array_column($roomDirectory['rooms'], 'channel'), 'forward room search must use available origin rooms');

$forward = $storage->forwardMessage($sourceChat['id'], $guest['id'], 'https://zero.home:50443', [
    'senderType'=>'user', 'senderId'=>'17', 'senderName'=>'Alice', 'senderEmblem'=>'/alice.jpg',
    'targetType'=>'user', 'targetId'=>'22', 'clientMessageId'=>'forward-1', 'createdAt'=>2000,
]);
chatExpectSame('17_22', $forward['chat']['channel'], 'user destination must create deterministic private room');
chatExpectSame('user', $forward['message']['senderType'], 'forward must belong to forwarding registered user');
chatExpectSame('17', $forward['message']['senderId'], 'forward must use forwarding user identity');
chatExpectSame('Guest message', ChatForward::parts($forward['message']['text'])['text'], 'guest-authored messages must be forwardable');
$retry = $storage->forwardMessage($sourceChat['id'], $guest['id'], 'https://zero.home:50443', [
    'senderType'=>'user', 'senderId'=>'17', 'senderName'=>'Alice', 'senderEmblem'=>'/alice.jpg',
    'targetType'=>'user', 'targetId'=>'22', 'clientMessageId'=>'forward-1', 'createdAt'=>2001,
]);
chatExpectSame(false, $retry['inserted'], 'forward retry must be idempotent');

echo "PASS: chat forwarding storage is canonical, registered-only and idempotent\n";
