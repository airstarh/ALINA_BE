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

