-- Store mutable chat-user identity data once instead of copying it into every
-- message. user_service messages share the identity of their originating user
-- or guest, matching ChatUserIdentity::type().
CREATE TABLE IF NOT EXISTS `chat_user` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_type` VARCHAR(16) NOT NULL COMMENT 'Canonical identity type: user, guest, or bot',
    `user_id` VARCHAR(128) NOT NULL,
    `user_name` VARCHAR(200) NOT NULL,
    `user_emblem` VARCHAR(2048) NOT NULL DEFAULT '',
    `created_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds',
    `updated_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in milliseconds',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_chat_user_identity` (`user_type`, `user_id`)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Keep the newest name and emblem for each canonical identity while retaining
-- its first and latest observed timestamps.
INSERT INTO `chat_user` (
    `user_type`,
    `user_id`,
    `user_name`,
    `user_emblem`,
    `created_at`,
    `updated_at`
)
SELECT
    `ranked`.`canonical_user_type`,
    `ranked`.`sender_id`,
    `ranked`.`sender_name`,
    `ranked`.`sender_emblem`,
    `ranked`.`first_seen_at`,
    `ranked`.`last_seen_at`
FROM (
    SELECT
        CASE
            WHEN `sender_type` = 'user_service' AND `sender_id` REGEXP '^[0-9]+$' THEN 'user'
            WHEN `sender_type` = 'user_service' THEN 'guest'
            ELSE `sender_type`
        END AS `canonical_user_type`,
        `sender_id`,
        `sender_name`,
        `sender_emblem`,
        MIN(`created_at`) OVER (
            PARTITION BY
                CASE
                    WHEN `sender_type` = 'user_service' AND `sender_id` REGEXP '^[0-9]+$' THEN 'user'
                    WHEN `sender_type` = 'user_service' THEN 'guest'
                    ELSE `sender_type`
                END,
                `sender_id`
        ) AS `first_seen_at`,
        MAX(`created_at`) OVER (
            PARTITION BY
                CASE
                    WHEN `sender_type` = 'user_service' AND `sender_id` REGEXP '^[0-9]+$' THEN 'user'
                    WHEN `sender_type` = 'user_service' THEN 'guest'
                    ELSE `sender_type`
                END,
                `sender_id`
        ) AS `last_seen_at`,
        ROW_NUMBER() OVER (
            PARTITION BY
                CASE
                    WHEN `sender_type` = 'user_service' AND `sender_id` REGEXP '^[0-9]+$' THEN 'user'
                    WHEN `sender_type` = 'user_service' THEN 'guest'
                    ELSE `sender_type`
                END,
                `sender_id`
            ORDER BY `created_at` DESC, `id` DESC
        ) AS `identity_rank`
    FROM `chat_message`
) AS `ranked`
WHERE `ranked`.`identity_rank` = 1;

ALTER TABLE `chat_message`
    ADD COLUMN `chat_user_id` BIGINT UNSIGNED NULL AFTER `chat_id`;

UPDATE `chat_message` AS `message`
INNER JOIN `chat_user` AS `identity`
    ON `identity`.`user_type` = CASE
        WHEN `message`.`sender_type` = 'user_service' AND `message`.`sender_id` REGEXP '^[0-9]+$' THEN 'user'
        WHEN `message`.`sender_type` = 'user_service' THEN 'guest'
        ELSE `message`.`sender_type`
    END
    AND `identity`.`user_id` = `message`.`sender_id`
SET `message`.`chat_user_id` = `identity`.`id`;

ALTER TABLE `chat_message`
    MODIFY COLUMN `chat_user_id` BIGINT UNSIGNED NOT NULL,
    DROP INDEX `uq_chat_message_group_sender`,
    ADD UNIQUE KEY `uq_chat_message_group_user` (
        `chat_id`,
        `client_message_id`,
        `sender_type`,
        `chat_user_id`
    ),
    ADD KEY `ix_chat_message_chat_user_id` (`chat_user_id`),
    ADD CONSTRAINT `fk_chat_message_chat_user`
        FOREIGN KEY (`chat_user_id`) REFERENCES `chat_user` (`id`) ON DELETE RESTRICT,
    DROP COLUMN `sender_id`,
    DROP COLUMN `sender_name`,
    DROP COLUMN `sender_emblem`;
