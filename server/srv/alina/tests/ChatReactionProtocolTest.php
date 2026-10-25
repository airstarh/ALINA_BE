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
foreach ([[$alice, 1, 'Alice'], [$bob, 2, 'Bob']] as [$client, $id, $name]) {
    $server->onOpen($client);
    chatSend($server, $client, 'join', ['channel' => 'reactions', 'CurrentUser' => ['id' => $id, 'nickname' => $name]]);
}
chatSend($server, $alice, 'message', ['channel' => 'reactions', 'msg' => 'React here', 'clientMessageId' => 'reaction-message']);
$messageId = $alice->lastEvent('message')['message']['id'];

chatSend($server, $alice, 'react_message', ['channel' => 'reactions', 'messageId' => $messageId, 'reaction' => 1]);
chatExpectSame(['positiveCount' => 1, 'negativeCount' => 0, 'currentReaction' => 1], $alice->lastEvent('reaction_changed')['reactions'] ?? null, 'positive reaction must toggle on for its user');
chatExpectSame(0, $bob->lastEvent('reaction_changed')['reactions']['currentReaction'] ?? null, 'reaction broadcast must be personalized');

chatSend($server, $alice, 'react_message', ['channel' => 'reactions', 'messageId' => $messageId, 'reaction' => -1]);
chatExpectSame(['positiveCount' => 0, 'negativeCount' => 1, 'currentReaction' => -1], $alice->lastEvent('reaction_changed')['reactions'] ?? null, 'opposite reaction must replace the previous state');
chatSend($server, $alice, 'react_message', ['channel' => 'reactions', 'messageId' => $messageId, 'reaction' => -1]);
chatExpectSame(['positiveCount' => 0, 'negativeCount' => 0, 'currentReaction' => 0], $alice->lastEvent('reaction_changed')['reactions'] ?? null, 'active reaction must toggle off');

chatSend($server, $alice, 'react_message', ['channel' => 'reactions', 'messageId' => $messageId, 'reaction' => 1]);
chatSend($server, $bob, 'react_message', ['channel' => 'reactions', 'messageId' => $messageId, 'reaction' => -1]);
chatSend($server, $alice, 'reaction_list', ['channel' => 'reactions', 'messageId' => $messageId]);
$list = $alice->lastEvent('reaction_list');
chatExpectSame(['Alice'], array_column($list['positive'] ?? [], 'name'), 'positive people list must contain positive reactors');
chatExpectSame(['Bob'], array_column($list['negative'] ?? [], 'name'), 'negative people list must contain negative reactors');

echo "PASS: chat reactions toggle, switch, broadcast and list people\n";
