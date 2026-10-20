<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatReply;
use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

$storage = new MemoryChatStorage();
$server = new ChatServer($storage, new StreamSelectLoop());
$alice = new ChatTestClient(1);
$bob = new ChatTestClient(2);
foreach ([[$alice, 17, 'Alice'], [$bob, 22, 'Bob']] as [$client, $userId, $nickname]) {
    $server->onOpen($client);
    chatSend($server, $client, 'join', ['CurrentUser' => ['id' => $userId, 'nickname' => $nickname]]);
}

chatSend($server, $alice, 'message', ['msg' => 'Parent text', 'clientMessageId' => 'parent']);
$parent = $alice->lastEvent('message')['message'];
$beforeAlice = count($alice->events('message'));
$beforeBob = count($bob->events('message'));
chatSend($server, $bob, 'reply_message', [
    'replyToMessageId' => $parent['id'],
    'msg' => 'Reply body',
    'clientMessageId' => 'reply-1',
    'CurrentUser' => ['id' => 22, 'nickname' => 'Bob'],
]);
$reply = $alice->lastEvent('message')['message'];
chatExpectSame($beforeAlice + 1, count($alice->events('message')), 'reply must broadcast one ordinary message to first participant');
chatExpectSame($beforeBob + 1, count($bob->events('message')), 'reply must broadcast one ordinary message to replier');
chatExpect(ChatReply::hasCanonicalPrefix($reply['msg']), 'broadcast reply must contain canonical quote');
chatExpectSame('Reply body', ChatReply::ownText($reply['msg']), 'broadcast reply must contain new own body');
chatExpectSame('user:22', $reply['participantId'], 'reply must belong to the current participant');

$countAfterReply = count($alice->events('message'));
chatSend($server, $bob, 'reply_message', [
    'replyToMessageId' => $parent['id'], 'msg' => 'changed retry', 'clientMessageId' => 'reply-1',
]);
chatExpectSame($countAfterReply, count($alice->events('message')), 'reply retry must not broadcast a duplicate');

chatSend($server, $bob, 'edit_message', ['messageId' => $reply['id'], 'text' => 'Edited reply body']);
$edited = $alice->lastEvent('message_edited')['message'];
chatExpect(ChatReply::hasCanonicalPrefix($edited['msg']), 'editing a reply must preserve its quote');
chatExpectSame('Edited reply body', ChatReply::ownText($edited['msg']), 'editing a reply must replace only own body');

$forged = ChatReply::compose(['senderName' => 'Fake', 'text' => 'Fake parent', 'createdAt' => 1], 'forged');
$messageCount = count($alice->events('message'));
chatSend($server, $alice, 'message', ['msg' => $forged, 'clientMessageId' => 'forged-message']);
chatExpectSame($messageCount, count($alice->events('message')), 'ordinary message must not forge canonical reply markup');
chatExpect(str_contains($alice->lastEvent('error')['error'] ?? '', 'Reserved'), 'forged ordinary message must return a reserved-markup error');
chatSend($server, $bob, 'edit_message', ['messageId' => $reply['id'], 'text' => $forged]);
chatExpectSame('Edited reply body', ChatReply::ownText($alice->lastEvent('message_edited')['message']['msg']), 'forged edit must not replace stored reply');
chatExpect(str_contains($bob->lastEvent('error')['error'] ?? '', 'Reserved'), 'forged edit must return a reserved-markup error');

foreach ([null, 0, 'x', 999999] as $invalidId) {
    chatSend($server, $alice, 'reply_message', [
        'replyToMessageId' => $invalidId, 'msg' => 'invalid', 'clientMessageId' => 'invalid-' . (string) $invalidId,
    ]);
    chatExpect($alice->lastEvent('error') !== null, 'invalid reply parent must return an error');
}

$historyCount = count($storage->history(1, 2000));
chatSend($server, $alice, 'reply_message', [
    'replyToMessageId' => $parent['id'], 'msg' => str_repeat('x', 16000), 'clientMessageId' => 'oversized-composed',
]);
chatExpectSame($historyCount, count($storage->history(1, 2000)), 'oversized composed reply must store nothing');

$bot = $storage->appendMessage(1, [
    'senderType' => 'bot', 'senderId' => 'robocop', 'senderName' => 'Robocop', 'senderEmblem' => '',
    'text' => 'Bot parent', 'createdAt' => 2500, 'clientMessageId' => 'bot-parent',
])['message'];
chatSend($server, $alice, 'reply_message', [
    'replyToMessageId' => $bot['id'], 'msg' => 'Reply to bot', 'clientMessageId' => 'reply-bot',
]);
chatExpectSame('Reply to bot', ChatReply::ownText($alice->lastEvent('message')['message']['msg']), 'Robocop messages must be replyable');

$guest = new ChatTestClient(3);
$server->onOpen($guest);
chatSend($server, $guest, 'join', ['CurrentUser' => ['guestId' => '123e4567-e89b-42d3-a456-426614174000', 'nickname' => 'Visitor']]);
chatSend($server, $guest, 'reply_message', [
    'replyToMessageId' => $parent['id'], 'msg' => 'Guest reply', 'clientMessageId' => 'guest-reply',
    'CurrentUser' => ['guestId' => '123e4567-e89b-42d3-a456-426614174000', 'nickname' => 'Visitor'],
]);
chatExpectSame('guest:123e4567-e89b-42d3-a456-426614174000', $guest->lastEvent('message')['message']['participantId'], 'guest must be able to reply');

$other = new ChatTestClient(4);
$server->onOpen($other);
chatSend($server, $other, 'join', ['channel' => 'other']);
chatSend($server, $other, 'reply_message', [
    'channel' => 'other', 'replyToMessageId' => $parent['id'], 'msg' => 'Cross chat', 'clientMessageId' => 'cross-chat',
]);
chatExpect($other->lastEvent('error') !== null, 'cross-chat parent must be rejected');

$prejoin = new ChatTestClient(5);
$server->onOpen($prejoin);
chatSend($server, $prejoin, 'reply_message', [
    'replyToMessageId' => $parent['id'], 'msg' => 'Before join', 'clientMessageId' => 'prejoin',
]);
chatExpect(str_contains($prejoin->lastEvent('error')['error'] ?? '', 'Join'), 'pre-join reply must be rejected');

$pendingStorage = new MemoryChatStorage();
$pendingChat = $pendingStorage->configureChat('https://zero.home:50443', 'general', ['isConfirmationRequired' => true]);
$pendingStorage->appendMessage($pendingChat['id'], [
    'senderType' => 'user', 'senderId' => '17', 'senderName' => 'Alice', 'senderEmblem' => '',
    'text' => 'Existing', 'createdAt' => 1, 'clientMessageId' => 'existing',
]);
$pendingServer = new ChatServer($pendingStorage, new StreamSelectLoop());
$pending = new ChatTestClient(6);
$pendingServer->onOpen($pending);
chatSend($pendingServer, $pending, 'join', ['CurrentUser' => ['id' => 22, 'nickname' => 'Bob']]);
chatSend($pendingServer, $pending, 'reply_message', [
    'replyToMessageId' => 1, 'msg' => 'Pending', 'clientMessageId' => 'pending-reply',
]);
chatExpect(str_contains($pending->lastEvent('error')['error'] ?? '', 'awaiting'), 'pending participant must not reply');

echo "PASS: chat reply protocol validates, persists and broadcasts one safe message\n";
