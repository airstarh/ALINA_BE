<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatServer;
use alina\Services\Chat\ChatMentions;
use React\EventLoop\StreamSelectLoop;

$storage = new MemoryChatStorage();
$server = new ChatServer($storage, new StreamSelectLoop());
$origin = 'https://zero.home:50443';
chatExpectSame(1, count(ChatMentions::requested(array_fill(0, 23, ['senderType' => 'user', 'senderId' => '22'])) ?? []), 'mention limit applies after duplicate collapse');

$seedChat = $storage->openChat($origin, 'seed');
$storage->appendMessage($seedChat['id'], [
    'senderType' => 'user', 'senderId' => '22', 'senderName' => 'Codex', 'senderEmblem' => '/codex.png',
    'text' => 'seed', 'createdAt' => 100, 'clientMessageId' => 'seed-22',
]);
$storage->appendMessage($seedChat['id'], [
    'senderType' => 'user_service', 'senderId' => '22', 'senderName' => 'Stale copied name', 'senderEmblem' => '/stale.png',
    'text' => 'mention', 'createdAt' => 300, 'clientMessageId' => 'seed-service',
]);
$storage->appendMessage($seedChat['id'], [
    'senderType' => 'guest', 'senderId' => 'b7c8ea87-34e4-49a1-ae9b-13de4fe35429', 'senderName' => 'CHROMIUM (Guest b7c8ea87)', 'senderEmblem' => '',
    'text' => 'seed', 'createdAt' => 200, 'clientMessageId' => 'seed-guest',
]);

$alice = new ChatTestClient(1, $origin);
$server->onOpen($alice);
chatSend($server, $alice, 'join');

chatSend($server, $alice, 'mentions_directory');
$directory = $alice->lastEvent('mentions_directory');
chatExpectSame(['22', 'b7c8ea87-34e4-49a1-ae9b-13de4fe35429'], array_column($directory['candidates'] ?? [], 'senderId'), 'mention directory is recent-first and origin-scoped');
chatExpectSame('Codex', $directory['candidates'][0]['senderName'] ?? null, 'latest genuine snapshot is preferred over copied service data');

chatSend($server, $alice, 'message', [
    'msg' => '@Codex hello',
    'clientMessageId' => 'mention-group-1',
    'mentions' => [['senderType' => 'user', 'senderId' => '22', 'senderName' => 'FORGED']],
]);
$events = array_values(array_filter($alice->events('message'), static fn (array $event): bool => ($event['message']['clientMessageId'] ?? '') === 'mention-group-1'));
chatExpectSame(2, count($events), 'one target creates one service message and one ordinary message');
chatExpectSame('user_service', $events[0]['message']['senderType'] ?? null, 'service message broadcasts first');
chatExpectSame('22', $events[0]['message']['senderId'] ?? null, 'service row belongs to target');
chatExpectSame('Alice mentions Codex', $events[0]['message']['msg'] ?? null, 'socket uses canonical target name');
chatExpectSame('user', $events[1]['message']['senderType'] ?? null, 'ordinary initiator message broadcasts last');
$presence = $alice->lastEvent('presence');
$mentionedPeople = array_column($presence['participants'] ?? [], null, 'id');
chatExpectSame(false, $mentionedPeople['user:22']['online'] ?? null, 'mentioned offline creature appears in the participant list');
chatExpectSame(false, $mentionedPeople['user:22']['inChat'] ?? null, 'mentioned offline creature is not marked in-chat');

chatSend($server, $alice, 'message', [
    'msg' => '@Codex hello', 'clientMessageId' => 'mention-group-1',
    'mentions' => [['senderType' => 'user', 'senderId' => '22']],
]);
$retried = array_values(array_filter($alice->events('message'), static fn (array $event): bool => ($event['message']['clientMessageId'] ?? '') === 'mention-group-1'));
chatExpectSame(2, count($retried), 'retry does not broadcast duplicates');

chatExpect($storage->hasParticipantMessage($events[0]['message']['id'] ? $storage->openChat($origin, 'general')['id'] : 0, 'user', '22'), 'service mention is user participation evidence');

echo "PASS: mention protocol persists and broadcasts one atomic group\n";
