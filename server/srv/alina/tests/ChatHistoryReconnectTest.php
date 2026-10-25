<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatServer;
use React\EventLoop\StreamSelectLoop;

$storage = new MemoryChatStorage();
$loop = new StreamSelectLoop();
$scheduledTimers = (new ReflectionProperty($loop, 'timers'))->getValue($loop);
$server = new ChatServer($storage, $loop);
$a = new ChatTestClient(1);
$b = new ChatTestClient(2);
$replacement = new ChatTestClient(3);

$server->onOpen($a);
chatSend($server, $a, 'join', ['channel' => 'history-review']);

for ($index = 0; $index < 255; $index++) {
    chatSend($server, $a, 'message', [
        'channel' => 'history-review',
        'msg' => "message {$index}",
        'clientMessageId' => "history-{$index}",
    ]);
}

$server->onOpen($b);
chatSend($server, $b, 'join', ['channel' => 'history-review']);
$server->onClose($a);
$server->onOpen($replacement);
chatSend($server, $replacement, 'join', ['channel' => 'history-review']);
$initialHistory = $replacement->lastEvent('history');
chatExpectSame(200, count($initialHistory['messages'] ?? []), 'reconnect must load the latest 200 messages');
chatExpectSame(true, $initialHistory['hasOlder'] ?? null, 'initial history must report older rows');
$firstMessageId = $initialHistory['messages'][0]['id'] ?? null;
chatSend($server, $replacement, 'history_before', ['channel' => 'history-review', 'beforeMessageId' => $firstMessageId]);
$olderHistory = $replacement->lastEvent('history_older');
chatExpect(count($olderHistory['messages'] ?? []) > 0, 'older history request must return the preceding page');
chatExpectSame(false, $olderHistory['hasOlder'] ?? null, 'oldest history page must report completion');
$olderMessages = $olderHistory['messages'];
chatExpect((int) $olderMessages[array_key_last($olderMessages)]['id'] < (int) $firstMessageId, 'older page must end before its cursor');

$server->onClose($b);
$server->onClose($replacement);
$timers = (new ReflectionProperty($server, 'roomCleanupTimers'))->getValue($server);
chatExpectSame(1, count($timers), 'empty live room must have delayed cleanup');
$cleanup = array_values($timers)[0];
chatExpect($scheduledTimers->contains($cleanup), 'cleanup timer must be scheduled');

$returning = new ChatTestClient(4);
$server->onOpen($returning);
chatSend($server, $returning, 'join', ['channel' => 'history-review']);
chatExpectSame(200, count($returning->lastEvent('history')['messages'] ?? []), 'brief empty-room gap must retain the latest 200 messages');
chatExpect(! $scheduledTimers->contains($cleanup), 'returning connection must cancel pending cleanup');

$server->onClose($returning);
$timers = (new ReflectionProperty($server, 'roomCleanupTimers'))->getValue($server);
$cleanup = array_values($timers)[0];
chatExpectSame(120.0, $cleanup->getInterval(), 'cleanup must preserve the two-minute grace period');
($cleanup->getCallback())($cleanup);

$nextServer = new ChatServer($storage, new StreamSelectLoop());
$nextVisit = new ChatTestClient(5);
$nextServer->onOpen($nextVisit);
chatSend($nextServer, $nextVisit, 'join', ['channel' => 'history-review']);
chatExpectSame(200, count($nextVisit->lastEvent('history')['messages'] ?? []), 'live-room cleanup must not erase persisted history');

echo "PASS: cursor pagination preserves and loads persisted chat history\n";
