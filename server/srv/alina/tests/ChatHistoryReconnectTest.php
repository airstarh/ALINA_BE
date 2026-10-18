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

for ($index = 0; $index < 55; $index++) {
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
chatExpectSame(50, count($replacement->lastEvent('history')['messages'] ?? []), 'reconnect with another person online must retain latest 50 messages');

$server->onClose($b);
$server->onClose($replacement);
$timers = (new ReflectionProperty($server, 'roomCleanupTimers'))->getValue($server);
chatExpectSame(1, count($timers), 'empty live room must have delayed cleanup');
$cleanup = array_values($timers)[0];
chatExpect($scheduledTimers->contains($cleanup), 'cleanup timer must be scheduled');

$returning = new ChatTestClient(4);
$server->onOpen($returning);
chatSend($server, $returning, 'join', ['channel' => 'history-review']);
chatExpectSame(50, count($returning->lastEvent('history')['messages'] ?? []), 'brief empty-room gap must retain history');
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
chatExpectSame(50, count($nextVisit->lastEvent('history')['messages'] ?? []), 'live-room cleanup must not erase persisted history');

echo "PASS: live room cleanup preserves the latest 50 persisted messages\n";
