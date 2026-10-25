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

ALTER TABLE `chat_message`
    ADD COLUMN `chat_user_id` BIGINT UNSIGNED NULL AFTER `chat_id`,
    ADD KEY `ix_chat_message_chat_user_id` (`chat_user_id`);

INSERT INTO `chat_user` (`user_type`, `user_id`, `user_name`, `user_emblem`, `created_at`, `updated_at`)
SELECT
    CASE WHEN `sender_type` = 'user_service' THEN IF(`sender_id` REGEXP '^[0-9]+$', 'user', 'guest') ELSE `sender_type` END,
    `sender_id`,
    '',
    '',
    MIN(`created_at`),
    MAX(`created_at`)
FROM `chat_message`
GROUP BY CASE WHEN `sender_type` = 'user_service' THEN IF(`sender_id` REGEXP '^[0-9]+$', 'user', 'guest') ELSE `sender_type` END, `sender_id`
ON DUPLICATE KEY UPDATE `updated_at` = GREATEST(`chat_user`.`updated_at`, VALUES(`updated_at`));

UPDATE `chat_user` AS `identity`
JOIN `chat_message` AS `latest`
  ON `latest`.`id` = (
      SELECT `candidate`.`id`
      FROM `chat_message` AS `candidate`
      WHERE `candidate`.`sender_id` = `identity`.`user_id`
        AND (CASE WHEN `candidate`.`sender_type` = 'user_service' THEN IF(`candidate`.`sender_id` REGEXP '^[0-9]+$', 'user', 'guest') ELSE `candidate`.`sender_type` END) = `identity`.`user_type`
      ORDER BY `candidate`.`id` DESC
      LIMIT 1
  )
SET `identity`.`user_name` = `latest`.`sender_name`,
    `identity`.`user_emblem` = `latest`.`sender_emblem`;

UPDATE `chat_message` AS `message`
JOIN `chat_user` AS `identity`
  ON `identity`.`user_id` = `message`.`sender_id`
 AND `identity`.`user_type` = CASE WHEN `message`.`sender_type` = 'user_service' THEN IF(`message`.`sender_id` REGEXP '^[0-9]+$', 'user', 'guest') ELSE `message`.`sender_type` END
SET `message`.`chat_user_id` = `identity`.`id`
WHERE `message`.`chat_user_id` IS NULL;

ALTER TABLE `chat_message`
    DROP INDEX `uq_chat_message_group_sender`,
    ADD UNIQUE KEY `uq_chat_message_group_user` (`chat_id`, `client_message_id`, `sender_type`, `chat_user_id`),
    MODIFY COLUMN `chat_user_id` BIGINT UNSIGNED NOT NULL,
    ADD CONSTRAINT `fk_chat_message_chat_user`
        FOREIGN KEY (`chat_user_id`) REFERENCES `chat_user` (`id`),
    DROP COLUMN `sender_id`,
    DROP COLUMN `sender_name`,
    DROP COLUMN `sender_emblem`;
