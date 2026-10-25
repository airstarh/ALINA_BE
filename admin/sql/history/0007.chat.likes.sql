CREATE TABLE IF NOT EXISTS `chat_like` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chat_message_id` BIGINT UNSIGNED NOT NULL,
    `chat_user_id` BIGINT UNSIGNED NOT NULL,
    `reaction` TINYINT NOT NULL COMMENT 'Positive reaction: 1; negative reaction: -1',
    `created_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds',
    `updated_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_chat_like_message_user` (`chat_message_id`, `chat_user_id`),
    KEY `ix_chat_like_user` (`chat_user_id`),
    CONSTRAINT `chk_chat_like_reaction` CHECK (`reaction` IN (-1, 1)),
    CONSTRAINT `fk_chat_like_message`
        FOREIGN KEY (`chat_message_id`) REFERENCES `chat_message` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_like_user`
        FOREIGN KEY (`chat_user_id`) REFERENCES `chat_user` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
