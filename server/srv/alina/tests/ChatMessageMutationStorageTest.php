<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/support/ChatTestSupport.php';

$storage = new MemoryChatStorage();
$firstChat = $storage->openChat('https://zero.home:50443', 'first');
$secondChat = $storage->openChat('https://zero.home:50443', 'second');
$original = $storage->appendMessage($firstChat['id'], [
    'senderType' => 'user',
    'senderId' => '17',
    'senderName' => 'Alice',
    'senderEmblem' => '/alice.webp',
    'text' => 'original',
    'createdAt' => 123456,
    'clientMessageId' => 'original-id',
])['message'];
$neighbor = $storage->appendMessage($firstChat['id'], [
    'senderType' => 'user',
    'senderId' => '22',
    'senderName' => 'Bob',
    'senderEmblem' => '',
    'text' => 'neighbor',
    'createdAt' => 123457,
    'clientMessageId' => 'neighbor-id',
])['message'];

$updated = $storage->editMessage($firstChat['id'], $original['id'], 'corrected');
chatExpectSame(
    array_replace($original, ['text' => 'corrected']),
    $updated,
    'editing must preserve all stored metadata except text',
);
chatExpectSame(
    ['corrected', 'neighbor'],
    array_column($storage->history($firstChat['id']), 'text'),
    'edited text must persist in history',
);

try {
    $storage->editMessage($secondChat['id'], $original['id'], 'cross-chat');
    throw new RuntimeException('FAIL: cross-chat edit was accepted');
}
catch (RuntimeException $error) {
    chatExpectSame('Message not found.', $error->getMessage(), 'cross-chat edit must be rejected');
}

chatExpectSame(false, $storage->deleteMessage($secondChat['id'], $original['id']), 'cross-chat delete must return false');
chatExpectSame(false, $storage->deleteMessage($firstChat['id'], 999999), 'missing delete must return false');
chatExpectSame(true, $storage->deleteMessage($firstChat['id'], $original['id']), 'matching delete must return true');
chatExpectSame([$neighbor], $storage->history($firstChat['id']), 'deletion must retain neighboring messages unchanged');
chatExpectSame(false, $storage->deleteMessage($firstChat['id'], $original['id']), 'repeated delete must be harmless');

echo "PASS: chat storage edits and deletes only the selected chat message\n";
