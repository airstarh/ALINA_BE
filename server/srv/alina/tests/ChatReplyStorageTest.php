<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatReply;

function replyMessage(string $clientMessageId, string $text = 'My reply'): array
{
    return [
        'senderType' => 'user',
        'senderId' => '22',
        'senderName' => 'Bob',
        'senderEmblem' => '',
        'text' => $text,
        'createdAt' => 2000,
        'clientMessageId' => $clientMessageId,
    ];
}

$parent = [
    'senderName' => 'Alice & <Admin>',
    'text' => str_repeat('Ж', 160),
    'createdAt' => 1000,
];
$composed = ChatReply::compose($parent, 'My <reply>');
chatExpect(ChatReply::hasCanonicalPrefix($composed), 'composed reply must have the canonical prefix');
chatExpect(str_contains($composed, 'Alice &amp; &lt;Admin&gt;'), 'parent author must be escaped');
chatExpect(str_contains($composed, str_repeat('Ж', 150)), 'quote must retain 150 Unicode characters');
chatExpect(! str_contains($composed, str_repeat('Ж', 151)), 'quote must stop after 150 Unicode characters');
chatExpectSame('My <reply>', ChatReply::ownText($composed), 'own text must exclude the canonical quote');
chatExpectSame($composed, ChatReply::replaceOwnText($composed, 'My <reply>'), 'same-body edit must preserve canonical bytes');
$edited = ChatReply::replaceOwnText($composed, 'Edited body');
chatExpectSame('Edited body', ChatReply::ownText($edited), 'reply edit must replace only own text');
chatExpectSame(substr($composed, 0, -strlen('My <reply>')), substr($edited, 0, -strlen('Edited body')), 'reply edit must preserve quote prefix byte-for-byte');

$nested = ChatReply::compose([
    'senderName' => 'Bob',
    'text' => $composed,
    'createdAt' => 3000,
], 'Nested reply');
chatExpectSame(1, substr_count($nested, '<div class="reply-quote"'), 'replying to a reply must not nest quote blocks');
chatExpect(str_contains($nested, 'My &lt;reply&gt;'), 'nested reply must quote only the parent own text');

$multiline = ChatReply::compose([
    'senderName' => "Alice\nAdmin",
    'text' => "first line\nsecond line",
    'createdAt' => 3500,
], 'After multiline quote');
chatExpect(ChatReply::hasCanonicalPrefix($multiline), 'canonical reply must support line breaks in escaped parent fields');
chatExpectSame('After multiline quote', ChatReply::ownText($multiline), 'multiline quote must remain separable from own text');

$lookalike = '<div class="reply-quote">not canonical</div>text';
chatExpectSame(false, ChatReply::hasCanonicalPrefix($lookalike), 'malformed lookalike must not be canonical');
chatExpectSame($lookalike, ChatReply::ownText($lookalike), 'malformed lookalike must remain ordinary text');

$storage = new MemoryChatStorage();
$chat = $storage->openChat('https://zero.home:50443', 'general');
$otherChat = $storage->openChat('https://zero.home:50443', 'other');
$storedParent = $storage->appendMessage($chat['id'], [
    'senderType' => 'user',
    'senderId' => '17',
    'senderName' => 'Alice',
    'senderEmblem' => '',
    'text' => 'Original parent',
    'createdAt' => 1000,
    'clientMessageId' => 'parent',
])['message'];

$reply = $storage->appendReply($chat['id'], $storedParent['id'], replyMessage('reply-1'));
chatExpectSame(true, $reply['inserted'], 'first reply must insert');
chatExpectSame(2, count($storage->history($chat['id'])), 'reply must create exactly one stored row');
chatExpectSame('My reply', ChatReply::ownText($reply['message']['text']), 'stored reply must retain own body');

$storage->deleteMessage($chat['id'], $storedParent['id']);
$retry = $storage->appendReply($chat['id'], $storedParent['id'], replyMessage('reply-1', 'changed retry'));
chatExpectSame(false, $retry['inserted'], 'retry must resolve by client ID before looking up a deleted parent');
chatExpectSame($reply['message'], $retry['message'], 'retry must return the original stored reply');

$crossParent = $storage->appendMessage($otherChat['id'], [
    'senderType' => 'user', 'senderId' => '17', 'senderName' => 'Alice', 'senderEmblem' => '',
    'text' => 'Other room', 'createdAt' => 1000, 'clientMessageId' => 'other-parent',
])['message'];
foreach ([$crossParent['id'], 999999] as $invalidParentId) {
    try {
        $storage->appendReply($chat['id'], $invalidParentId, replyMessage('invalid-' . $invalidParentId));
        throw new RuntimeException('FAIL: missing or cross-chat parent must be rejected');
    }
    catch (RuntimeException $error) {
        chatExpectSame('Message not found.', $error->getMessage(), 'invalid parent must report not found');
    }
}

$editedStored = $storage->editMessage($chat['id'], $reply['message']['id'], 'Edited stored body');
chatExpectSame('Edited stored body', ChatReply::ownText($editedStored['text']), 'storage edit must replace only reply body');
chatExpect(ChatReply::hasCanonicalPrefix($editedStored['text']), 'storage edit must preserve reply quote');

for ($index = 0; $index < 1001; $index++) {
    $storage->appendMessage($chat['id'], [
        'senderType' => 'user', 'senderId' => '22', 'senderName' => 'Bob', 'senderEmblem' => '',
        'text' => 'bulk', 'createdAt' => 4000 + $index, 'clientMessageId' => 'bulk-' . $index,
    ]);
}
chatExpectSame(1000, count($storage->history($chat['id'], 2000)), 'reply storage must retain normal 1000-message pruning');

echo "PASS: chat reply formatting and storage are safe, scoped, idempotent and bounded\n";
