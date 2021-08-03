ALTER TABLE `tale`
    ADD COLUMN `geo_latitude` DECIMAL(20,6) NULL DEFAULT NULL AFTER `is_social_sharing_hidden`,
    ADD COLUMN `geo_longitude` DECIMAL(20,6) NULL DEFAULT NULL AFTER `geo_latitude`,
    ADD COLUMN `is_sticked_on_home` TINYINT NULL DEFAULT '0' AFTER `geo_longitude`;
