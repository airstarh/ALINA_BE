ALTER TABLE `tale`
    ADD COLUMN `is_date_hidden` TINYINT NULL DEFAULT '0' AFTER `is_header_hidden`;
