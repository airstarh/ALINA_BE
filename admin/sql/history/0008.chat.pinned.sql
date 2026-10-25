-- Per-user pinned chats.
-- Only registered identities (chat_user.user_type = 'user') will be allowed
-- to create rows by the application layer.
CREATE TABLE IF NOT EXISTS `chat_pinned` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chat_user_id` BIGINT UNSIGNED NOT NULL,
    `chat_id` BIGINT UNSIGNED NOT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds; also provides stable pin ordering',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_chat_pinned_user_chat` (`chat_user_id`, `chat_id`),
    KEY `ix_chat_pinned_chat` (`chat_id`),
    CONSTRAINT `fk_chat_pinned_user` FOREIGN KEY (`chat_user_id`) REFERENCES `chat_user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_pinned_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- A nullable generated key makes the uniqueness constraint apply only to the
-- pinned row: many NULL values are allowed, but only one chat_id may be pinned.
ALTER TABLE
    `chat_message`
ADD
    COLUMN `is_pinned` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Whether this is the single pinned message in its chat'
AFTER
    `message_text`,
ADD
    COLUMN `pinned_chat_id` BIGINT UNSIGNED GENERATED ALWAYS AS (
        CASE
            WHEN `is_pinned` = 1 THEN `chat_id`
            ELSE NULL
        END
    ) STORED
AFTER
    `is_pinned`,
ADD
    UNIQUE KEY `uq_chat_message_one_pinned_per_chat` (`pinned_chat_id`),
ADD
    CONSTRAINT `chk_chat_message_is_pinned` CHECK (`is_pinned` IN (0, 1));

-- Supports counting at most 5001 ordinary user messages per chat. The future
-- directory query will read a LIMIT 5001 subquery and render 5000+ when the
-- extra row exists, without scanning automated/service messages.
ALTER TABLE
    `chat_message`
ADD
    KEY `ix_chat_message_chat_sender_id` (`chat_id`, `sender_type`, `id`);
