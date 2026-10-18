-- Draft chat storage structure.
-- This file is intentionally review-only until it is executed manually.
CREATE TABLE IF NOT EXISTS `chat` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `origin` VARCHAR(255) NOT NULL,
    `channel_name_pseudo_id` VARCHAR(128) NOT NULL,
    `chat_name_human` VARCHAR(200) NOT NULL,
    `is_anion_allowed` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Whether anonymous participants may join',
    `is_confirmation_required` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Whether an existing participant must approve a new connection',
    `created_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds',
    `updated_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_chat_origin_channel` (`origin`, `channel_name_pseudo_id`)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_message` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chat_id` BIGINT UNSIGNED NOT NULL,
    `sender_type` VARCHAR(16) NOT NULL,
    `sender_id` VARCHAR(128) NOT NULL,
    `sender_name` VARCHAR(200) NOT NULL,
    `message_text` TEXT NOT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds',
    `client_message_id` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `ix_chat_message_chat_id` (`chat_id`),
    UNIQUE KEY `uq_chat_message_client_id` (`chat_id`, `client_message_id`)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
