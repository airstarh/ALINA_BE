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
$origin = 'https://zero.home:50443';
$guestId = 'b7c8ea87-34e4-49a1-ae9b-13de4fe35429';

$guest = new ChatTestClient(1, $origin);
$admin = new ChatTestClient(2, $origin);
$server->onOpen($guest);
$server->onOpen($admin);
chatSend($server, $guest, 'join', ['CurrentUser' => ['guestId' => $guestId, 'nickname' => 'Old name']]);
chatSend($server, $guest, 'message', ['CurrentUser' => ['guestId' => $guestId, 'nickname' => 'Old name'], 'msg' => 'first', 'clientMessageId' => 'guest-message-1']);
chatSend($server, $admin, 'join', ['CurrentUser' => ['id' => 17, 'nickname' => 'Admin']]);

chatSend($server, $admin, 'rename_guest', ['guestId' => $guestId, 'name' => 'New name']);
$renamed = $guest->lastEvent('guest_renamed');
chatExpectSame('guest:' . $guestId, $renamed['participantId'] ?? null, 'guest rename broadcasts the stable participant identity');
chatExpectSame('New name (Guest b7c8ea87)', $renamed['name'] ?? null, 'guest rename broadcasts canonical display name');

$chat = $storage->openChat($origin, 'general');
$history = $storage->history($chat['id'], 50);
chatExpectSame('New name (Guest b7c8ea87)', $history[0]['senderName'] ?? null, 'guest rename persists every historical author occurrence');
chatExpectSame('first', $history[0]['text'] ?? null, 'guest rename does not rewrite message text');

chatSend($server, $guest, 'join', ['CurrentUser' => ['guestId' => $guestId, 'nickname' => 'Self name']]);
chatExpectSame('Self name (Guest b7c8ea87)', $admin->lastEvent('guest_renamed')['name'] ?? null, 'guest self rename uses the same persistent broadcast flow');
chatExpectSame('Self name (Guest b7c8ea87)', $storage->history($chat['id'], 50)[0]['senderName'] ?? null, 'guest self rename persists historical occurrences');

echo "PASS: guest names persist and broadcast across the current chat\n";
