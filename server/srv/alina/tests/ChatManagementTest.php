<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

$origin = 'https://zero.home:50443';
$storage = new MemoryChatStorage();
$managedChat = $storage->configureChat($origin, 'managed', [
    'isConfirmationRequired' => true,
]);
$otherChat = $storage->configureChat($origin, 'other', [
    'isConfirmationRequired' => false,
]);
$server = new ChatServer($storage, new StreamSelectLoop());

$admin = new ChatTestClient(1, $origin);
$targetOne = new ChatTestClient(2, $origin);
$targetTwo = new ChatTestClient(3, $origin);
$targetOtherRoom = new ChatTestClient(4, $origin);

$server->onOpen($admin);
chatSend($server, $admin, 'join', [
    'channel' => 'managed',
    'CurrentUser' => ['id' => 1, 'nickname' => 'Admin'],
]);
$initialState = $admin->lastEvent('chat_state')['chat'] ?? null;
chatExpectSame([
    'name' => 'managed',
    'isAnionAllowed' => true,
    'isConfirmationRequired' => true,
], $initialState, 'admitted participant must receive current chat state');
chatSend($server, $admin, 'message', [
    'channel' => 'managed',
    'msg' => 'admin message',
    'clientMessageId' => 'admin-message',
    'CurrentUser' => ['id' => 1, 'nickname' => 'Admin'],
]);

$server->onOpen($targetOne);
chatSend($server, $targetOne, 'join', [
    'channel' => 'managed',
    'CurrentUser' => ['id' => 2, 'nickname' => 'Target'],
]);
chatSend($server, $admin, 'approve', [
    'channel' => 'managed',
    'participantId' => 'user:2',
    'CurrentUser' => ['id' => 1, 'nickname' => 'Admin'],
]);
chatSend($server, $targetOne, 'message', [
    'channel' => 'managed',
    'msg' => 'target message',
    'clientMessageId' => 'target-message',
    'CurrentUser' => ['id' => 2, 'nickname' => 'Target'],
]);

$server->onOpen($targetTwo);
chatSend($server, $targetTwo, 'join', [
    'channel' => 'managed',
    'CurrentUser' => ['id' => 2, 'nickname' => 'Target second tab'],
]);
chatExpect($targetTwo->lastEvent('joined') !== null, 'participant with retained message must reconnect automatically');

$server->onOpen($targetOtherRoom);
chatSend($server, $targetOtherRoom, 'join', [
    'channel' => 'other',
    'CurrentUser' => ['id' => 2, 'nickname' => 'Target elsewhere'],
]);
chatSend($server, $targetOtherRoom, 'message', [
    'channel' => 'other',
    'msg' => 'other room message',
    'clientMessageId' => 'other-target-message',
    'CurrentUser' => ['id' => 2, 'nickname' => 'Target elsewhere'],
]);

$unicodeName = str_repeat('Ж', 200);
chatSend($server, $admin, 'rename', [
    'channel' => 'managed',
    'name' => $unicodeName,
]);
chatExpectSame($unicodeName, $targetOne->lastEvent('chat_state')['chat']['name'] ?? null, '200-character Unicode name must be broadcast');

chatSend($server, $admin, 'rename', ['channel' => 'managed', 'name' => '   ']);
chatExpect($admin->lastEvent('error') !== null, 'empty chat name must be rejected');
chatSend($server, $admin, 'rename', [
    'channel' => 'managed',
    'name' => str_repeat('Я', 201),
]);
chatExpect($admin->lastEvent('error') !== null, 'chat name over 200 Unicode characters must be rejected');

chatSend($server, $admin, 'rename', ['channel' => 'managed', 'name' => 'First write']);
chatSend($server, $targetOne, 'rename', ['channel' => 'managed', 'name' => 'Final write']);
chatExpectSame('Final write', $admin->lastEvent('chat_state')['chat']['name'] ?? null, 'latest rename must win for every participant');
chatExpectSame('Final write', $storage->openChat($origin, 'managed')['name'], 'latest rename must be persisted');

chatSend($server, $admin, 'set_anonymous', [
    'channel' => 'managed',
    'allowed' => false,
]);
chatExpectSame(false, $targetOne->lastEvent('chat_state')['chat']['isAnionAllowed'] ?? null, 'anonymous policy change must be broadcast');
chatExpectSame(false, $storage->openChat($origin, 'managed')['isAnionAllowed'], 'anonymous policy change must be persisted');

$anonymous = new ChatTestClient(6, $origin);
$server->onOpen($anonymous);
chatSend($server, $anonymous, 'join', [
    'channel' => 'managed',
    'CurrentUser' => ['guestId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'nickname' => 'Anonymous'],
]);
chatExpect($anonymous->lastEvent('error') !== null, 'disabled anonymous access must reject a new guest');

chatSend($server, $admin, 'set_confirmation', [
    'channel' => 'managed',
    'required' => false,
]);
chatExpectSame(false, $targetOne->lastEvent('chat_state')['chat']['isConfirmationRequired'] ?? null, 'confirmation policy change must be broadcast');
chatExpectSame(false, $storage->openChat($origin, 'managed')['isConfirmationRequired'], 'confirmation policy change must be persisted');
chatSend($server, $admin, 'set_confirmation', [
    'channel' => 'managed',
    'required' => true,
]);
chatExpectSame(true, $storage->openChat($origin, 'managed')['isConfirmationRequired'], 'confirmation policy must toggle back to closed');

chatSend($server, $admin, 'kick', [
    'channel' => 'managed',
    'participantId' => 'user:2',
]);
$kickEvent = $admin->lastEvent('participant_kicked');
chatExpectSame('user:2', $kickEvent['participantId'] ?? null, 'kick event must identify the target');
chatExpectSame('bot:robocop', $kickEvent['message']['participantId'] ?? null, 'kick event must include Robocop message');
chatExpectSame('Robocop', $kickEvent['message']['CurrentUser']['name'] ?? null, 'Robocop must have its stable display name');
chatExpect($targetOne->closed && $targetTwo->closed, 'kick must close every target connection in the current chat');
chatExpect(! $targetOtherRoom->closed, 'kick must not close the same identity in another chat');

$managedHistory = $storage->history($managedChat['id'], 100);
chatExpectSame([], array_values(array_filter(
    $managedHistory,
    static fn (array $message): bool => $message['senderType'] === 'user' && $message['senderId'] === '2',
)), 'kick must delete all target messages from the current chat');
chatExpectSame(1, count(array_filter(
    $managedHistory,
    static fn (array $message): bool => $message['senderType'] === 'user' && $message['senderId'] === '1',
)), 'kick must retain messages from other participants');
$robocop = $managedHistory[array_key_last($managedHistory)];
chatExpectSame(['bot', 'robocop', 'Robocop'], [
    $robocop['senderType'],
    $robocop['senderId'],
    $robocop['senderName'],
], 'kick must persist a Robocop bot row');
chatExpectSame('other room message', $storage->history($otherChat['id'], 10)[0]['text'] ?? null, 'kick must not delete target messages from another chat');

$targetReturn = new ChatTestClient(5, $origin);
$server->onOpen($targetReturn);
chatSend($server, $targetReturn, 'join', [
    'channel' => 'managed',
    'CurrentUser' => ['id' => 2, 'nickname' => 'Target'],
]);
chatExpect($targetReturn->lastEvent('join_pending') !== null, 'kicked participant must lose automatic confirmation re-entry');

chatSend($server, $admin, 'kick', [
    'channel' => 'managed',
    'participantId' => 'user:1',
]);
chatExpect($admin->closed, 'self-kick must close the sender connection');
$managedHistory = $storage->history($managedChat['id'], 100);
chatExpectSame([], array_values(array_filter(
    $managedHistory,
    static fn (array $message): bool => $message['senderType'] === 'user' && $message['senderId'] === '1',
)), 'self-kick must delete the sender messages');
chatExpectSame(2, count(array_filter(
    $managedHistory,
    static fn (array $message): bool => $message['senderType'] === 'bot' && $message['senderId'] === 'robocop',
)), 'each kick must leave one persisted Robocop message');

echo "PASS: chat rename and kick management persist and broadcast the agreed behavior\n";
