<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/support/ChatTestSupport.php';

$storage = new MemoryChatStorage();
$root = $storage->openChat('https://zero.home:50443', 'root');
$source = $storage->appendMessage($root['id'], ['senderType'=>'user','senderId'=>'1','senderName'=>'A','senderEmblem'=>'','text'=>'root','createdAt'=>1,'clientMessageId'=>'r'])['message'];
$child = $storage->createDiscussion($root['id'], $source['id'])['chat'];
$childSource = $storage->appendMessage($child['id'], ['senderType'=>'user','senderId'=>'1','senderName'=>'A','senderEmblem'=>'','text'=>'child','createdAt'=>2,'clientMessageId'=>'c'])['message'];
$storage->createDiscussion($child['id'], $childSource['id']);
chatExpectSame(3, $storage->chatCount(), 'fixture must have three generations');
$deleted = $storage->deleteChat('https://zero.home:50443', 'root');
chatExpectSame(3, count($deleted['channels']), 'recursive deletion must include descendants');
chatExpectSame(0, $storage->chatCount(), 'recursive deletion must remove all chats');

$parent = $storage->openChat('https://zero.home:50443', 'parent');
$message = $storage->appendMessage($parent['id'], ['senderType'=>'user','senderId'=>'1','senderName'=>'A','senderEmblem'=>'','text'=>'again','createdAt'=>3,'clientMessageId'=>'again'])['message'];
$discussion = $storage->createDiscussion($parent['id'], $message['id'])['chat'];
$restored = $storage->deleteChat('https://zero.home:50443', $discussion['channel'])['restored'];
chatExpectSame($message['id'], $restored['sourceMessageId'], 'deleting a child must identify restored parent source');
chatExpectSame(null, $storage->discussionForMessage($parent['id'], $message['id']), 'parent message must be able to discuss again');
echo "PASS: recursive chat deletion removes discussion descendants\n";
