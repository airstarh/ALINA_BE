-- Durable, generic queue for asynchronous file processing and notifications.
-- A normal `file` row is created only after a job completes successfully.
CREATE TABLE IF NOT EXISTS `file_job` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_id` CHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'uploading',
    `owner_id` BIGINT UNSIGNED NOT NULL,
    `entity_table` VARCHAR(100) NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `source_tmp` VARCHAR(2048) NULL,
    `result_tmp` VARCHAR(2048) NULL,
    `original_name` VARCHAR(1000) NULL,
    `mime` VARCHAR(255) NULL,
    `payload` JSON NULL,
    `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `error` TEXT NULL,
    `worker_id` VARCHAR(100) NULL,
    `lease_until` BIGINT UNSIGNED NULL,
    `created_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in seconds',
    `started_at` BIGINT UNSIGNED NULL COMMENT 'Unix timestamp in seconds',
    `finished_at` BIGINT UNSIGNED NULL COMMENT 'Unix timestamp in seconds',
    `updated_at` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp in seconds',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_file_job_public_id` (`public_id`),
    KEY `ix_file_job_status_created` (`status`, `created_at`),
    KEY `ix_file_job_owner_created` (`owner_id`, `created_at`),
    KEY `ix_file_job_entity` (`entity_table`, `entity_id`),
    KEY `ix_file_job_lease_until` (`lease_until`),
    CONSTRAINT `chk_file_job_status` CHECK (
        `status` IN ('uploading', 'queued', 'processing', 'completed', 'failed', 'cancelled')
    ),
    CONSTRAINT `chk_file_job_progress` CHECK (`progress` BETWEEN 0 AND 100)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
