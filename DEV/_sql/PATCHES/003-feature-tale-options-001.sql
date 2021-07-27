ALTER TABLE `tale`
    ADD COLUMN `is_sticked` TINYINT(4) NULL DEFAULT '0' AFTER `iframe`,
    ADD COLUMN `is_header_hidden` TINYINT(4) NULL DEFAULT '0' AFTER `is_sticked`;
