CREATE TABLE `pm_organization`
(
    `id`         BIGINT       NOT NULL AUTO_INCREMENT,
    `name_human` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`)
)
    COLLATE = 'utf8mb4_unicode_ci'
;

CREATE TABLE `pm_department`
(
    `id`                 BIGINT       NOT NULL AUTO_INCREMENT,
    `name_human`         VARCHAR(100) NOT NULL,
    `pm_organization_id` BIGINT       NOT NULL,
    PRIMARY KEY (`id`)
)
    COLLATE = 'utf8mb4_unicode_ci'
;

CREATE TABLE `pm_project`
(
    `id`               BIGINT       NOT NULL AUTO_INCREMENT,
    `name_human`       VARCHAR(100) NOT NULL,
    `pm_department_id` BIGINT       NOT NULL,
    PRIMARY KEY (`id`)
)
    COLLATE = 'utf8mb4_unicode_ci'
;

CREATE TABLE `pm_task`
(
    `id`            BIGINT       NOT NULL AUTO_INCREMENT,
    `name_human`    VARCHAR(100) NOT NULL,
    `pm_project_id` BIGINT       NOT NULL,
    PRIMARY KEY (`id`)
)
    COLLATE = 'utf8mb4_unicode_ci'
;

CREATE TABLE `pm_subtask`
(
    `id`             BIGINT         NOT NULL AUTO_INCREMENT,
    `name_human`     VARCHAR(100)   NOT NULL,
    `pm_task_id`     BIGINT         NOT NULL,
    `time_estimated` DECIMAL(10, 2) NOT NULL DEFAULT (0),
    `price`          DECIMAL(10, 2) NOT NULL DEFAULT (0),
    PRIMARY KEY (`id`)
)
    COLLATE = 'utf8mb4_unicode_ci'
;

CREATE TABLE `pm_executed_subtasks`
(
    `id`                 BIGINT         NOT NULL AUTO_INCREMENT,
    `pm_organization_id` BIGINT         NOT NULL,
    `pm_department_id`   BIGINT         NOT NULL,
    `pm_project_id`      BIGINT         NOT NULL,
    `pm_task_id`         BIGINT         NOT NULL,
    `pm_subtask_id`      BIGINT         NOT NULL,
    `amount`             DECIMAL(10, 2) NOT NULL DEFAULT (0),
    `time_spent`         DECIMAL(10, 2) NOT NULL DEFAULT (0),
    `created_at`         BIGINT(20)     NOT NULL DEFAULT (UNIX_TIMESTAMP()),
    PRIMARY KEY (`id`)
)
    COLLATE = 'utf8mb4_unicode_ci'
;

-- `home.zero`.audit definition

-- `home.zero`.audit definition

CREATE TABLE `home.zero`.audit
(
    id         BIGINT auto_increment             NOT NULL,
    `at`       BIGINT DEFAULT (UNIX_TIMESTAMP()) NOT NULL,
    user_id    BIGINT                            NULL,
    table_name varchar(100)                      NOT NULL,
    table_id   BIGINT                            NOT NULL,
    event_name varchar(100)                      NOT NULL,
    event_data json                              NULL,
    CONSTRAINT audit_PK PRIMARY KEY (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
CREATE INDEX audit_at_IDX USING BTREE ON `home.zero`.audit (`at` DESC);