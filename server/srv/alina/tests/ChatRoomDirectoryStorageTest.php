<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/support/ChatTestSupport.php';

function roomMessage(
    string $clientMessageId,
    string $senderType,
    string $senderId,
    int $createdAt,
): array {
    return [
        'senderType' => $senderType,
        'senderId' => $senderId,
        'senderName' => $senderType === 'bot' ? 'Robocop' : $senderId,
        'senderEmblem' => '',
        'text' => $clientMessageId,
        'createdAt' => $createdAt,
        'clientMessageId' => $clientMessageId,
    ];
}

function addRoomMessage(
    MemoryChatStorage $storage,
    array $chat,
    string $clientMessageId,
    string $senderType,
    string $senderId,
    int $createdAt,
): void {
    $storage->appendMessage(
        $chat['id'],
        roomMessage($clientMessageId, $senderType, $senderId, $createdAt),
    );
}

$storage = new MemoryChatStorage();
$origin = 'https://zero.home:50443';
$otherOrigin = 'https://vov.home:50443';

$mineNewest = $storage->configureChat($origin, 'mine-newest', ['name' => 'Я новый']);
addRoomMessage($storage, $mineNewest, 'm1', 'user', '17', 500);
addRoomMessage($storage, $mineNewest, 'm2', 'user', '17', 600);
addRoomMessage($storage, $mineNewest, 'm3', 'guest', 'guest-other', 700);
addRoomMessage($storage, $mineNewest, 'm4', 'bot', 'robocop', 800);

$mineTie = $storage->configureChat($origin, 'mine-tie', ['name' => 'Mine tie']);
addRoomMessage($storage, $mineTie, 't1', 'user', '17', 400);
addRoomMessage($storage, $mineTie, 't2', 'guest', 'guest-mine', 700);
addRoomMessage($storage, $mineTie, 'MENTION:test', 'user_service', 'guest-mine', 690);

$guestMine = $storage->configureChat($origin, 'guest-mine', ['name' => 'Guest room']);
addRoomMessage($storage, $guestMine, 'g1', 'guest', 'guest-mine', 650);

$kicked = $storage->configureChat($origin, 'kicked', ['name' => 'Kicked room']);
addRoomMessage($storage, $kicked, 'k1', 'user', '17', 900);
addRoomMessage($storage, $kicked, 'k2', 'user', '22', 910);
$storage->deleteParticipantMessages($kicked['id'], 'user', '17');

$botOnly = $storage->configureChat($origin, 'bot-only', ['name' => 'Bot only']);
addRoomMessage($storage, $botOnly, 'b1', 'bot', 'robocop', 950);
$empty = $storage->configureChat($origin, 'empty', ['name' => 'Empty']);

$foreign = $storage->configureChat($otherOrigin, 'foreign', ['name' => 'Foreign']);
addRoomMessage($storage, $foreign, 'f1', 'user', '17', 999);

$mine = $storage->listRooms($origin, 'user', '17', [
    'mine' => ['limit' => 1, 'offset' => 1],
    'others' => ['limit' => 2, 'offset' => 1],
]);

chatExpectSame(2, $mine['mine']['total'], 'all matching mine rooms must be counted');
chatExpectSame(1, $mine['mine']['limit'], 'mine limit must be returned independently');
chatExpectSame(1, $mine['mine']['offset'], 'mine offset must be returned independently');
chatExpectSame(['mine-tie'], array_column($mine['mine']['rows'], 'channel'), 'mine offset must be applied after latest-message ordering');
chatExpectSame(2, $mine['mine']['rows'][0]['participantCount'], 'duplicate sender messages and bots must not inflate participant count');
chatExpectSame(700, $mine['mine']['rows'][0]['latestMessageAt'], 'latest date includes every retained message');

chatExpectSame(4, $mine['others']['total'], 'kicked, guest, bot-only and empty rooms must remain other rooms');
chatExpectSame(['kicked', 'guest-mine'], array_column($mine['others']['rows'], 'channel'), 'other pagination and latest ordering must be independent');
chatExpectSame(1, $mine['others']['rows'][0]['participantCount'], 'deleted kicked-user messages must no longer count');

$allOthers = $storage->listRooms($origin, 'user', '17', [
    'mine' => ['limit' => 1000, 'offset' => 0],
    'others' => ['limit' => 1000, 'offset' => 0],
]);
chatExpectSame(['bot-only', 'kicked', 'guest-mine', 'empty'], array_column($allOthers['others']['rows'], 'channel'), 'empty rooms must sort last and foreign-origin rooms must be absent');
chatExpectSame(null, $allOthers['others']['rows'][3]['latestMessageAt'], 'empty room latest date must be null');
chatExpectSame(0, $allOthers['others']['rows'][0]['participantCount'], 'bot-only rooms must have zero participants');

$guest = $storage->listRooms($origin, 'guest', 'guest-mine', [
    'mine' => ['limit' => 1000, 'offset' => 0],
    'others' => ['limit' => 1000, 'offset' => 0],
]);
chatExpectSame(['mine-tie', 'guest-mine'], array_column($guest['mine']['rows'], 'channel'), 'guest participation must use sender type and persistent ID');
chatExpectSame(true, $guest['mine']['rows'][0]['isMentioned'], 'a room containing a mention notification for the identity must be marked');
chatExpectSame(690, $guest['mine']['rows'][0]['latestMentionAt'], 'latest mention timestamp must be returned for acknowledgement');
chatExpectSame(false, $guest['mine']['rows'][1]['isMentioned'], 'ordinary participation must not be marked as a mention');

echo "PASS: room directory storage partitions, aggregates, paginates and isolates origins\n";
