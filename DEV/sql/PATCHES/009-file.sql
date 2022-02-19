ALTER TABLE `file`
    ADD COLUMN `owner_id` BIGINT NULL DEFAULT NULL AFTER `container`;

ALTER TABLE `file`
    ADD COLUMN `url_path` VARCHAR(500) DEFAULT NULL AFTER `owner_id`;

ALTER TABLE `file`
    ADD COLUMN `order` INT NULL DEFAULT NULL AFTER `url_path`;

ALTER TABLE `file`
    CHANGE COLUMN `container` `container` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci' AFTER `name_human`,
    ADD COLUMN `root_id` BIGINT(20) NULL DEFAULT NULL AFTER `container`,
    ADD COLUMN `level` TINYINT NULL DEFAULT '1' AFTER `parent_id`,
    ADD COLUMN `dir` VARCHAR(500) NULL DEFAULT NULL AFTER `url_path`,
    ADD COLUMN `created_at` BIGINT(20) NULL DEFAULT NULL AFTER `dir`;

