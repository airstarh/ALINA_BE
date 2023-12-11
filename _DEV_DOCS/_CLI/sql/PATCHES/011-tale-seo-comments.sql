ALTER TABLE `tale`
    ADD COLUMN `is_comment_for_owner` TINYINT       NULL DEFAULT '0',
    ADD COLUMN `seo_index`            DECIMAL(1, 1) NULL DEFAULT '0.3'
