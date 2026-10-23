ALTER TABLE `file`
    ADD COLUMN `size` BIGINT UNSIGNED NULL DEFAULT NULL
        COMMENT 'File size in bytes'
        AFTER `name_human`;
