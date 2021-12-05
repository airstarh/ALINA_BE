CREATE TABLE `router_alias`
(
    `id`       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`    VARCHAR(2000)       NOT NULL COLLATE 'utf8mb4_general_ci',
    `url`      VARCHAR(2000)       NOT NULL COLLATE 'utf8mb4_general_ci',
    `table`    VARCHAR(50)         NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `table_id` BIGINT(20)          NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
)
    COLLATE = 'utf8mb4_general_ci'
    ENGINE = InnoDB
;
