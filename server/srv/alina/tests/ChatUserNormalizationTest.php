<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$expect = static function (bool $condition, string $message): void {
    if (! $condition) throw new RuntimeException($message);
};

$identityFile = __DIR__ . '/../Services/Chat/ChatUserIdentity.php';
$expect(file_exists($identityFile), 'ChatUserIdentity must exist');
require $identityFile;

use alina\Services\Chat\ChatUserIdentity;

$expect(ChatUserIdentity::type('user', '42') === 'user', 'registered messages keep user identity');
$expect(ChatUserIdentity::type('user_service', '42') === 'user', 'registered service messages share the user identity');
$expect(ChatUserIdentity::type('user_service', 'guest-id') === 'guest', 'guest service messages share the guest identity');
$expect(ChatUserIdentity::type('bot', 'robocop') === 'bot', 'bot identity remains distinct');

$storage = file_get_contents(__DIR__ . '/../Services/Chat/DatabaseChatStorage.php');
$expect(str_contains($storage, 'upsertChatUser'), 'database writes must resolve the canonical chat user');
$expect(str_contains($storage, "'chatUserId'"), 'message writes must retain the chat_user relation');
$messageModel = file_get_contents(__DIR__ . '/../mvc/Model/chat_message.php');
$userModel = file_get_contents(__DIR__ . '/../mvc/Model/chat_user.php');
$expect(str_contains($userModel, 'function nowMilliseconds'), 'chat user model must own its timestamp helper');
$expect(! str_contains($messageModel, "'sender_emblem'"), 'message model must not write emblem');
$expect(! str_contains($messageModel, "'sender_name'"), 'message model must not write user name');
$expect(! str_contains($messageModel, "'sender_id'"), 'message model must not write user ID');
$expect(substr_count($messageModel, "q('chat_message')") >= 2, 'all joined message queries must provide the base-model alias explicitly');

echo "PASS: chat user identity normalization contract\n";
