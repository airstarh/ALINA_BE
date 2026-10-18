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
$storage->configureChat($origin, 'members', [
    'isAnionAllowed' => false,
    'isConfirmationRequired' => false,
]);
$server = new ChatServer($storage, new StreamSelectLoop());

$anonymous = new ChatTestClient(1, $origin);
$server->onOpen($anonymous);
chatSend($server, $anonymous, 'join', [
    'channel' => 'members',
    'CurrentUser' => [
        'guestId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        'nickname' => 'Anon',
    ],
]);
chatExpect($anonymous->lastEvent('error') !== null, 'anonymous participant must be rejected when anonymous joins are disabled');
chatExpectSame(null, $anonymous->lastEvent('joined'), 'rejected anonymous participant must not be admitted');

$member = new ChatTestClient(2, $origin);
$server->onOpen($member);
chatSend($server, $member, 'join', [
    'channel' => 'members',
    'CurrentUser' => ['id' => 2, 'nickname' => 'Member'],
]);
chatExpect($member->lastEvent('joined') !== null, 'confirmation-disabled chat must admit a registered participant automatically');

$storage->configureChat($origin, 'bootstrap', ['isConfirmationRequired' => true]);
$bootstrapFirst = new ChatTestClient(3, $origin);
$bootstrapSecond = new ChatTestClient(4, $origin);
$server->onOpen($bootstrapFirst);
$server->onOpen($bootstrapSecond);
chatSend($server, $bootstrapFirst, 'join', [
    'channel' => 'bootstrap',
    'CurrentUser' => ['id' => 3, 'nickname' => 'First'],
]);
chatSend($server, $bootstrapSecond, 'join', [
    'channel' => 'bootstrap',
    'CurrentUser' => ['id' => 4, 'nickname' => 'Second'],
]);
chatExpect($bootstrapFirst->lastEvent('joined') !== null, 'first participant must bootstrap an empty confirmation-required chat');
chatExpect($bootstrapSecond->lastEvent('join_pending') !== null, 'only the first live participant may use the empty-chat bootstrap');

$storage->configureChat($origin, 'confirm', [
    'isAnionAllowed' => true,
    'isConfirmationRequired' => true,
]);
$writer = new ChatTestClient(10, $origin);
$server->onOpen($writer);
chatSend($server, $writer, 'join', [
    'channel' => 'confirm',
    'CurrentUser' => ['id' => 10, 'nickname' => 'Writer'],
]);
chatExpect($writer->lastEvent('joined') !== null, 'first participant must enter a message-empty confirmation-required chat');
chatSend($server, $writer, 'message', [
    'channel' => 'confirm',
    'msg' => 'I was here',
    'clientMessageId' => 'writer-history',
    'CurrentUser' => ['id' => 10, 'nickname' => 'Writer'],
]);

$newcomer = new ChatTestClient(11, $origin);
$server->onOpen($newcomer);
chatSend($server, $newcomer, 'join', [
    'channel' => 'confirm',
    'CurrentUser' => ['id' => 11, 'nickname' => 'Newcomer'],
]);
chatExpect($newcomer->lastEvent('join_pending') !== null, 'new identity must wait in a confirmation-required chat with history');
chatExpectSame(null, $newcomer->lastEvent('history'), 'pending participant must not receive history');
chatExpectSame(null, $newcomer->lastEvent('presence'), 'pending participant must not receive presence');
chatExpectSame('user:11', $writer->lastEvent('join_request')['participant']['id'] ?? null, 'admitted participant must receive the pending join request');

chatSend($server, $newcomer, 'message', [
    'channel' => 'confirm',
    'msg' => 'not admitted',
    'clientMessageId' => 'pending-message',
    'CurrentUser' => ['id' => 11, 'nickname' => 'Newcomer'],
]);
chatExpect($newcomer->lastEvent('error') !== null, 'pending participant must not send messages');

chatSend($server, $writer, 'approve', [
    'channel' => 'confirm',
    'participantId' => 'user:11',
    'CurrentUser' => ['id' => 10, 'nickname' => 'Writer'],
]);
chatExpect($newcomer->lastEvent('join_approved') !== null, 'any admitted participant must be able to approve a request');
chatExpect($newcomer->lastEvent('history') !== null, 'approved participant must receive history');
$presence = $newcomer->lastEvent('presence')['participants'] ?? [];
chatExpectSame(['user:10', 'user:11'], array_column($presence, 'id'), 'approved participant must enter live presence');

$server->onClose($writer);
$server->onClose($newcomer);

$waitingAlone = new ChatTestClient(12, $origin);
$server->onOpen($waitingAlone);
chatSend($server, $waitingAlone, 'join', [
    'channel' => 'confirm',
    'CurrentUser' => ['id' => 12, 'nickname' => 'Waiting'],
]);
chatExpect($waitingAlone->lastEvent('join_pending') !== null, 'new identity must remain pending when nobody is connected');

$returningWriter = new ChatTestClient(13, $origin);
$server->onOpen($returningWriter);
chatSend($server, $returningWriter, 'join', [
    'channel' => 'confirm',
    'CurrentUser' => ['id' => 10, 'nickname' => 'Writer'],
]);
chatExpect($returningWriter->lastEvent('joined') !== null, 'retained writer must re-enter automatically');
chatExpectSame('user:12', $returningWriter->lastEvent('join_request')['participant']['id'] ?? null, 'returning writer must see requests that arrived while chat had no admitted users');

chatSend($server, $returningWriter, 'join', [
    'channel' => 'confirm',
    'CurrentUser' => ['id' => 999, 'nickname' => 'Impersonator'],
]);
chatExpect(
    str_contains($returningWriter->lastEvent('error')['error'] ?? '', 'identity'),
    'an admitted socket must not change its claimed identity inside the room',
);
chatSend($server, $returningWriter, 'message', [
    'channel' => 'confirm',
    'msg' => 'identity remains bound',
    'clientMessageId' => 'bound-identity',
    'CurrentUser' => ['id' => 999, 'nickname' => 'Impersonator'],
]);
chatExpectSame('user:10', $returningWriter->lastEvent('message')['message']['participantId'] ?? null, 'messages must retain the join-bound identity');

$disconnecting = new ChatTestClient(14, $origin);
$server->onOpen($disconnecting);
chatSend($server, $disconnecting, 'join', [
    'channel' => 'confirm',
    'CurrentUser' => ['id' => 14, 'nickname' => 'Gone'],
]);
chatExpect($disconnecting->lastEvent('join_pending') !== null, 'additional newcomer must become pending');
$server->onClose($disconnecting);
$requestsBeforeReconnect = count($returningWriter->events('join_request'));
$server->onClose($returningWriter);

$writerAgain = new ChatTestClient(15, $origin);
$server->onOpen($writerAgain);
chatSend($server, $writerAgain, 'join', [
    'channel' => 'confirm',
    'CurrentUser' => ['id' => 10, 'nickname' => 'Writer'],
]);
$newRequests = array_slice($writerAgain->events('join_request'), 0);
chatExpectSame(['user:12'], array_column(array_column($newRequests, 'participant'), 'id'), 'disconnected pending participant must be removed from pending state');

$guestId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$storage->configureChat($origin, 'guest-confirm', ['isConfirmationRequired' => true]);
$guest = new ChatTestClient(20, $origin);
$server->onOpen($guest);
chatSend($server, $guest, 'join', [
    'channel' => 'guest-confirm',
    'CurrentUser' => ['guestId' => $guestId, 'nickname' => 'Guest writer'],
]);
chatSend($server, $guest, 'message', [
    'channel' => 'guest-confirm',
    'msg' => 'guest history',
    'clientMessageId' => 'guest-history',
    'CurrentUser' => ['guestId' => $guestId, 'nickname' => 'Guest writer'],
]);
$server->onClose($guest);
$guestReturn = new ChatTestClient(21, $origin);
$server->onOpen($guestReturn);
chatSend($server, $guestReturn, 'join', [
    'channel' => 'guest-confirm',
    'CurrentUser' => ['guestId' => $guestId, 'nickname' => 'Guest writer'],
]);
chatExpect($guestReturn->lastEvent('joined') !== null, 'retained guest writer must re-enter automatically');

echo "PASS: anonymous and confirmation policies admit, pend, approve and clean up correctly\n";
