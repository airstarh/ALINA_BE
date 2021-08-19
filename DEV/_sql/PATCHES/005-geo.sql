ALTER TABLE `tale`
    ADD COLUMN `geo_latitude` DECIMAL(20,6) NULL DEFAULT NULL AFTER `is_social_sharing_hidden`,
    ADD COLUMN `geo_longitude` DECIMAL(20,6) NULL DEFAULT NULL AFTER `geo_latitude`,
    ADD COLUMN `is_sticked_on_home` TINYINT NULL DEFAULT '0' AFTER `geo_longitude`;

ALTER TABLE `tale`
    ADD COLUMN `geo_map_type` VARCHAR(50) NULL DEFAULT 'hybrid' COLLATE 'utf8mb4_unicode_ci' AFTER `geo_longitude`,
    ADD COLUMN `geo_zoom` INT(11) NULL DEFAULT '14' AFTER `geo_map_type`;

ALTER TABLE `tale`
    ADD COLUMN `geo_is_map_shown` TINYINT NOT NULL DEFAULT 0 AFTER `geo_map_type`;
