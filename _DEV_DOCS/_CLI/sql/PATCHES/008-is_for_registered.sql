ALTER TABLE `tale`
    ADD COLUMN `is_for_registered` TINYINT(4) NULL DEFAULT '0' AFTER `is_sticked_on_home`;
