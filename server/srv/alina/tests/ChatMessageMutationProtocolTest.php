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
$bob = new ChatTestClient(2);

foreach ([$alice, $bob] as $client) {
    $server->onOpen($client);
    chatSend($server, $client, 'join', ['channel' => 'general']);
}

chatSend($server, $alice, 'message', [
    'channel' => 'general',
    'msg' => 'original',
    'clientMessageId' => 'mutation-original',
]);
$original = $alice->lastEvent('message')['message'];

chatSend($server, $bob, 'edit_message', [
    'channel' => 'general',
    'messageId' => $original['id'],
    'text' => '  corrected  ',
]);
$edited = $alice->lastEvent('message_edited')['message'] ?? null;
chatExpect(is_array($edited), 'edit must broadcast to every admitted participant');
chatExpectSame('corrected', $edited['msg'], 'edit must trim and replace text');
foreach (['id', 'participantId', 'timestamp', 'clientMessageId', 'CurrentUser'] as $field) {
    chatExpectSame($original[$field], $edited[$field], 'edit must preserve ' . $field);
}

foreach ([0, -1, 'abc', '1.2'] as $invalidId) {
    chatSend($server, $alice, 'edit_message', ['messageId' => $invalidId, 'text' => 'valid']);
    chatExpect(str_contains($alice->lastEvent('error')['error'] ?? '', 'message ID'), 'invalid edit ID must be rejected');
}
foreach (['', '   ', str_repeat('x', 16001)] as $invalidText) {
    chatSend($server, $alice, 'edit_message', ['messageId' => $original['id'], 'text' => $invalidText]);
    chatExpect(str_contains($alice->lastEvent('error')['error'] ?? '', '16000'), 'invalid edit text must be rejected');
}

$other = new ChatTestClient(3);
$server->onOpen($other);
chatSend($server, $other, 'join', ['channel' => 'other']);
chatSend($server, $other, 'delete_message', ['channel' => 'other', 'messageId' => $original['id']]);
chatExpect(str_contains($other->lastEvent('error')['error'] ?? '', 'store'), 'cross-chat delete must be rejected');

$unjoined = new ChatTestClient(4);
$server->onOpen($unjoined);
chatSend($server, $unjoined, 'delete_message', ['messageId' => $original['id']]);
chatExpect(str_contains($unjoined->lastEvent('error')['error'] ?? '', 'Join'), 'pre-join mutation must be rejected');

$pendingStorage = new MemoryChatStorage();
$pendingChat = $pendingStorage->configureChat('https://zero.home:50443', 'locked', ['isConfirmationRequired' => true]);
$pendingStorage->appendMessage($pendingChat['id'], [
    'senderType' => 'user', 'senderId' => '99', 'senderName' => 'Existing', 'senderEmblem' => '',
    'text' => 'seed', 'createdAt' => 1, 'clientMessageId' => 'seed',
]);
$pendingServer = new ChatServer($pendingStorage, new StreamSelectLoop());
$pendingClient = new ChatTestClient(5);
$pendingServer->onOpen($pendingClient);
chatSend($pendingServer, $pendingClient, 'join', ['channel' => 'locked', 'CurrentUser' => ['id' => 77]]);
chatSend($pendingServer, $pendingClient, 'edit_message', ['channel' => 'locked', 'messageId' => 1, 'text' => 'blocked']);
chatExpect(str_contains($pendingClient->lastEvent('error')['error'] ?? '', 'awaiting'), 'pending participant must not mutate messages');

$storage->nextError = new RuntimeException('temporary');
chatSend($server, $alice, 'edit_message', ['messageId' => $original['id'], 'text' => 'failure']);
chatExpect(str_contains($alice->lastEvent('error')['error'] ?? '', 'store'), 'storage failure must become a non-fatal error');
chatSend($server, $alice, 'edit_message', ['messageId' => $original['id'], 'text' => 'after failure']);
chatExpectSame('after failure', $alice->lastEvent('message_edited')['message']['msg'] ?? null, 'worker must survive edit failure');
chatSend($server, $alice, 'edit_message', ['messageId' => $original['id'], 'text' => 'after failure']);
chatExpectSame('after failure', $bob->lastEvent('message_edited')['message']['msg'] ?? null, 'duplicate edit must remain harmless');

chatSend($server, $alice, 'delete_message', ['messageId' => $original['id']]);
chatExpectSame($original['id'], $bob->lastEvent('message_deleted')['messageId'] ?? null, 'delete must broadcast string message ID');
chatExpectSame([], array_values(array_filter($storage->history(1), fn (array $message): bool => $message['senderType'] !== 'user_service')), 'delete must remove the persisted ordinary message while retaining presence history');
chatSend($server, $alice, 'delete_message', ['messageId' => $original['id']]);
chatExpect(str_contains($alice->lastEvent('error')['error'] ?? '', 'store'), 'duplicate delete must be a harmless not-found error');

echo "PASS: socket edits and deletes joined-chat messages and broadcasts mutations\n";
