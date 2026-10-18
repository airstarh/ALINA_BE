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

$zero = new ChatTestClient(1, 'https://ZERO.HOME:50443');
$vov = new ChatTestClient(2, 'https://vov:50443');
$server->onOpen($zero);
$server->onOpen($vov);
chatSend($server, $zero, 'join', ['channel' => '1']);
chatSend($server, $vov, 'join', ['channel' => '1']);
chatExpectSame(2, $storage->chatCount(), 'same channel on two origins must create two chats');

$beforeVovMessages = count($vov->events('message'));
chatSend($server, $zero, 'message', [
    'channel' => '1',
    'msg' => 'zero only',
    'clientMessageId' => 'zero-1',
]);
chatExpectSame($beforeVovMessages, count($vov->events('message')), 'messages must not cross origins');
$zeroMessage = $zero->lastEvent('message')['message'] ?? null;
chatExpect(is_array($zeroMessage), 'sender must receive persisted message event');
chatExpect(is_int($zeroMessage['timestamp'] ?? null), 'server timestamp must be Unix milliseconds');
chatExpect(abs($zeroMessage['timestamp'] - (int) floor(microtime(true) * 1000)) < 5000, 'server timestamp must be current');

chatSend($server, $zero, 'message', [
    'channel' => '1',
    'msg' => 'duplicate body is ignored',
    'clientMessageId' => 'zero-1',
]);
chatExpectSame(1, count($zero->events('message')), 'duplicate client message ID must not broadcast twice');

chatSend($server, $zero, 'message', [
    'channel' => 'different',
    'msg' => 'must not switch room',
    'clientMessageId' => 'wrong-room',
]);
chatExpectSame(1, count($zero->events('message')), 'message event must not change the joined room');
chatExpect($zero->lastEvent('error') !== null, 'room-changing message must receive an error');

$storage->nextError = new RuntimeException('database unavailable');
chatSend($server, $zero, 'message', [
    'channel' => '1',
    'msg' => 'fails safely',
    'clientMessageId' => 'db-error',
]);
chatExpect(str_contains($zero->lastEvent('error')['error'] ?? '', 'store'), 'storage exception must become a client error');
chatSend($server, $zero, 'message', [
    'channel' => '1',
    'msg' => 'server survived',
    'clientMessageId' => 'after-error',
]);
chatExpectSame('server survived', $zero->lastEvent('message')['message']['msg'] ?? null, 'storage exception must not terminate server');

$restartedServer = new ChatServer($storage, new StreamSelectLoop());
$returning = new ChatTestClient(3, 'https://zero.home:50443');
$restartedServer->onOpen($returning);
chatSend($restartedServer, $returning, 'join', ['channel' => '1']);
chatExpectSame(
    ['zero only', 'server survived'],
    array_column($returning->lastEvent('history')['messages'] ?? [], 'msg'),
    'history must survive server reconstruction',
);

$missingOrigin = new ChatTestClient(4, null);
$server->onOpen($missingOrigin);
chatSend($server, $missingOrigin, 'join', ['channel' => 'missing']);
chatExpect($missingOrigin->lastEvent('error') !== null, 'missing Origin must reject join');

$invalidOrigin = new ChatTestClient(5, 'ftp://zero.home');
$server->onOpen($invalidOrigin);
chatSend($server, $invalidOrigin, 'join', ['channel' => 'invalid']);
chatExpect($invalidOrigin->lastEvent('error') !== null, 'non-HTTP Origin must reject join');

$invalidChannel = new ChatTestClient(6, 'https://zero.home:50443');
$server->onOpen($invalidChannel);
chatSend($server, $invalidChannel, 'join', ['channel' => 'not allowed']);
chatExpect($invalidChannel->lastEvent('error') !== null, 'channel outside the safe alphabet must reject join');

echo "PASS: chat persistence is origin-scoped, durable, idempotent and failure-safe\n";
