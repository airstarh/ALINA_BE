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

insert into `file`
(`entity_id`,
 `entity_table`,
 `name_fs`, `name_human`, `url_path`, `dir`, `container`, `root_id`, `parent_id`, `level`, `owner_id`, `created_at`, `order`)
values
(897,
 tale, 42654fe4815a9d67e698807b4818af20.png, 1512408781245.png, //saysimsim.ru/uploads/25/42654fe4815a9d67e698807b4818af20.png, /var/www/saysimsim.ru/uploads/25/42654fe4815a9d67e698807b4818af20.png, FILE, null, null, 1, 25, 1645317940, 0)


