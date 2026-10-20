<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatDiscussion.php';
require __DIR__ . '/support/ChatTestSupport.php';

use alina\Services\Chat\ChatDiscussion;

chatExpectSame('DISCUSS OF: message 9', ChatDiscussion::title(9, 'VOICE:https://example.test/a.webm'), 'voice must use prefixed fallback title');
chatExpectSame('DISCUSS OF: message 9', ChatDiscussion::title(9, "<div class=\"reply-quote\" data-timestamp=\"1\"><div class=\"reply-quote-author\">A</div><div class=\"reply-quote-text\">old</div></div>\nanswer"), 'reply must use prefixed fallback title');
chatExpectSame('DISCUSS OF: message 9', ChatDiscussion::title(9, 'https://example.test/image.webp'), 'media must use prefixed fallback title');

$storage = new MemoryChatStorage();
$parent = $storage->openChat('https://zero.home:50443', 'general');
$source = $storage->appendMessage($parent['id'], [
    'senderType' => 'user', 'senderId' => '17', 'senderName' => 'Alice', 'senderEmblem' => '',
    'text' => 'A useful message to discuss', 'createdAt' => 1000, 'clientMessageId' => 'source-1',
])['message'];

$discussion = $storage->createDiscussion($parent['id'], $source['id']);
chatExpectSame(true, $discussion['created'], 'first request must create a discussion');
chatExpectSame('DISCUSS OF: A useful message to discuss', $discussion['chat']['name'], 'plain text must receive the discussion prefix');
chatExpectSame(0, $discussion['responseCount'], 'seed must not count as a response');
chatExpectSame($source['text'], $discussion['seedMessage']['text'], 'seed must copy source text');

$retry = $storage->createDiscussion($parent['id'], $source['id']);
chatExpectSame(false, $retry['created'], 'retry must reuse the same discussion');
chatExpectSame($discussion['chat']['id'], $retry['chat']['id'], 'one source must have one child chat');

try {
    $storage->deleteMessage($parent['id'], $source['id']);
    throw new RuntimeException('FAIL: discussion source deletion must be rejected');
}
catch (RuntimeException $error) {
    chatExpect(str_contains($error->getMessage(), 'discussion'), 'source deletion must explain discussion protection');
}

try {
    $storage->deleteMessage($discussion['chat']['id'], $discussion['seedMessage']['id']);
    throw new RuntimeException('FAIL: discussion seed deletion must be rejected');
}
catch (RuntimeException $error) {
    chatExpect(str_contains($error->getMessage(), 'discussion'), 'seed deletion must explain discussion protection');
}

$storage->appendMessage($discussion['chat']['id'], [
    'senderType' => 'user', 'senderId' => '18', 'senderName' => 'Bob', 'senderEmblem' => '',
    'text' => 'A response', 'createdAt' => 2000, 'clientMessageId' => 'response-1',
]);
$state = $storage->discussionForMessage($parent['id'], $source['id']);
chatExpectSame(1, $state['responseCount'], 'only messages after seed count as responses');

for ($index = 2; $index <= 1001; $index++) {
    $storage->appendMessage($discussion['chat']['id'], [
        'senderType' => 'user', 'senderId' => '18', 'senderName' => 'Bob', 'senderEmblem' => '',
        'text' => 'Response ' . $index, 'createdAt' => 2000 + $index, 'clientMessageId' => 'response-' . $index,
    ]);
}
$retained = $storage->history($discussion['chat']['id'], 1000);
chatExpectSame($source['text'], $retained[0]['text'], 'retention must never prune discussion seed');
chatExpectSame(999, $storage->discussionForMessage($parent['id'], $source['id'])['responseCount'], 'seed plus latest 999 responses must respect 1000-row limit');

echo "PASS: discussion storage creates one protected child and counts responses\n";
