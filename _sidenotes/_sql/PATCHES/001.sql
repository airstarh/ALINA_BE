ALTER TABLE `tale`
    ADD COLUMN `lang` VARCHAR(10) DEFAULT 'RU' NULL AFTER `is_submitted`;
-- #####
-- #####
-- #####
ALTER TABLE `tale`
    ADD COLUMN `body_txt` TEXT NULL AFTER `body`;

ALTER TABLE `alina`.`notification`
    ADD COLUMN `id_root` BIGINT NULL COMMENT 'parent' AFTER `created_at`,
    ADD COLUMN `id_answer` BIGINT NULL COMMENT 'expand' AFTER `id_root`,
    ADD COLUMN `id_highlight` BIGINT NULL COMMENT 'highlight' AFTER `id_answer`;

-- SECS
ALTER TABLE `alina`.`watch_visit`
    ADD COLUMN `ip` CHAR (46) NULL AFTER `ip_id`,
    ADD COLUMN `browser_enc` VARCHAR (256) NULL AFTER `browser_id`;

ALTER TABLE `alina`.`watch_banned_visit`
    DROP COLUMN `ip_id`,
    DROP COLUMN `browser_id`,
    ADD COLUMN `ip` CHAR (46) NULL AFTER `id`,
    ADD COLUMN `browser_enc` VARCHAR (256) NULL AFTER `ip`,
    CHANGE `reason` `reason` VARCHAR (300) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'spam' NULL AFTER `browser_enc`;


