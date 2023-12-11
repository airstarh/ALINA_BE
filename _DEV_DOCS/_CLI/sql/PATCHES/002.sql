CREATE TABLE `router_alias`
(
    `id`    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias` VARCHAR(2000)       NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
    `url`   VARCHAR(2000)       NOT NULL DEFAULT '' COLLATE 'utf8mb4_unicode_ci',
    PRIMARY KEY (`id`) USING BTREE
)
    COLLATE = 'utf8mb4_unicode_ci'
    ENGINE = InnoDB
;
