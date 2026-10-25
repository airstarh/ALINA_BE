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

$sql = file_get_contents(__DIR__ . '/../../../../admin/sql/migrate/sql.sql');
$expect(str_contains($sql, 'CREATE TABLE IF NOT EXISTS `chat_user`'), 'migration must create chat_user');
$expect(str_contains($sql, '`chat_user_id` BIGINT UNSIGNED NULL'), 'migration must add a safe nullable relation before backfill');
$expect(preg_match('/CREATE TABLE IF NOT EXISTS `chat_user` \((.*?)\) ENGINE/s', $sql, $tableMatch) === 1, 'migration must define chat_user');
$expect(! str_contains($tableMatch[1], '`chat_id`'), 'global chat identity must not belong to one chat');
$expect(str_contains($sql, 'UNIQUE KEY `uq_chat_user_identity` (`user_type`, `user_id`)'), 'one identity must be shared by all chats');
$expect(str_contains($sql, 'UPDATE `chat_message` AS `message`'), 'migration must backfill existing messages');
$expect(str_contains($sql, 'MODIFY COLUMN `chat_user_id` BIGINT UNSIGNED NOT NULL'), 'relation must be mandatory after backfill');
$expect(str_contains($sql, 'DROP COLUMN `sender_id`'), 'message must not retain user ID');
$expect(str_contains($sql, 'DROP COLUMN `sender_name`'), 'message must not retain user name');
$expect(str_contains($sql, 'DROP COLUMN `sender_emblem`'), 'message must not retain user emblem');
$expect(str_contains($sql, '`chat_user_id`'), 'message uniqueness must use the normalized relation');

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
