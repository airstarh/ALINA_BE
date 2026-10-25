<?php

declare(strict_types=1);

$sql = file_get_contents(__DIR__ . '/../../../../admin/sql/history/0007.chat.likes.sql');
$expect = static function (bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); };
$expect(str_contains($sql, 'CREATE TABLE IF NOT EXISTS `chat_like`'), 'migration must create chat_like');
$expect(str_contains($sql, 'UNIQUE KEY `uq_chat_like_message_user` (`chat_message_id`, `chat_user_id`)'), 'one user reaction per message must be enforced');
$expect(str_contains($sql, 'CHECK (`reaction` IN (-1, 1))'), 'database must reject unknown reaction values');
$expect(str_contains($sql, 'FOREIGN KEY (`chat_message_id`) REFERENCES `chat_message` (`id`) ON DELETE CASCADE'), 'message deletion must remove reactions');
$expect(str_contains($sql, 'FOREIGN KEY (`chat_user_id`) REFERENCES `chat_user` (`id`) ON DELETE CASCADE'), 'reaction must reference normalized chat identity');
echo "PASS: chat reaction migration contract\n";
