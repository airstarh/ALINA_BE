-- DONE

ALTER TABLE tale
    DROP INDEX IND_TALE_PUBLISH_AT;
ALTER TABLE tale
    DROP INDEX IND_TALE_CREATED_AT;
CREATE INDEX tale_publish_at_IDX USING BTREE ON tale (publish_at DESC);
ALTER TABLE watch_visit
    DROP INDEX IND_WV_VISITED_AT;
CREATE INDEX watch_visit_visited_at_IDX USING BTREE ON watch_visit (visited_at DESC);

CREATE TABLE audit
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
CREATE INDEX audit_at_IDX USING BTREE ON audit (`at` DESC);

-- NOR DONE

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

--

CREATE TABLE `home.zero`.pm_work
(
    id                 BIGINT auto_increment NOT NULL,
    pm_organization_id BIGINT                NULL,
    pm_department_id   BIGINT                NULL,
    pm_project_id      BIGINT                NULL,
    pm_task_id         BIGINT                NULL,
    pm_subtask_id      BIGINT                NULL,
    CONSTRAINT pm_work_PK PRIMARY KEY (id),
    CONSTRAINT pm_work_FK_1 FOREIGN KEY (pm_department_id) REFERENCES `home.zero`.pm_department (id),
    CONSTRAINT pm_work_FK FOREIGN KEY (pm_organization_id) REFERENCES `home.zero`.pm_organization (id),
    CONSTRAINT pm_work_FK_2 FOREIGN KEY (pm_project_id) REFERENCES `home.zero`.pm_project (id),
    CONSTRAINT pm_work_FK_3 FOREIGN KEY (pm_task_id) REFERENCES `home.zero`.pm_task (id),
    CONSTRAINT pm_work_FK_4 FOREIGN KEY (pm_subtask_id) REFERENCES `home.zero`.pm_subtask (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;


