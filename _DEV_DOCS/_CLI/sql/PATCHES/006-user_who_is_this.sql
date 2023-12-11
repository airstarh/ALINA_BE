CREATE TABLE `user_to_me`
(
    `id`           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_user_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
    `to_user_id`   BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
    `description`  VARCHAR(512)        NULL     DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    PRIMARY KEY (`id`) USING BTREE
)
    COLLATE = 'utf8mb4_general_ci'
    ENGINE = InnoDB
;
