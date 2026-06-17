-- diag_report.sql
-- Comprehensive MySQL Health & Performance Diagnostic Script
-- All comments in English for consistency
-- === SYSTEM INFO ===
SELECT
    '=== MySQL System Information ===' AS `--`;

SELECT
    VERSION() AS `MySQL Version`;

SELECT
    CAST(
        (
            SELECT
                VARIABLE_VALUE
            FROM
                performance_schema.global_variables
            WHERE
                VARIABLE_NAME = 'innodb_buffer_pool_size'
        ) AS UNSIGNED
    ) / 1024 / 1024 / 1024 AS `InnoDB Buffer Pool (GB)`;

SELECT
    CAST(
        (
            SELECT
                VARIABLE_VALUE
            FROM
                performance_schema.global_variables
            WHERE
                VARIABLE_NAME = 'innodb_log_file_size'
        ) AS UNSIGNED
    ) / 1024 / 1024 AS `InnoDB Log File Size (MB)`;

SELECT
    CAST(
        (
            SELECT
                VARIABLE_VALUE
            FROM
                performance_schema.global_variables
            WHERE
                VARIABLE_NAME = 'max_allowed_packet'
        ) AS UNSIGNED
    ) / 1024 / 1024 AS `Max Allowed Packet (MB)`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'innodb_flush_log_at_trx_commit'
    ) AS `Flush Log at Commit (mode)`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'sync_binlog'
    ) AS `Sync Binlog`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'innodb_flush_method'
    ) AS `Flush Method`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'skip_name_resolve'
    ) AS `Skip Name Resolve (DNS)`;

-- === DISK & TABLE STATE ===
SELECT
    '=== Disk and Table State ===' AS `--`;

SELECT
    table_schema AS `Database`,
    COUNT(*) AS `Table Count`
FROM
    information_schema.tables
WHERE
    table_schema NOT IN (
        'information_schema',
        'performance_schema',
        'mysql',
        'sys'
    )
GROUP BY
    table_schema;

SELECT
    table_schema AS `Database`,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS `Size (MB)`
FROM
    information_schema.tables
WHERE
    table_schema NOT IN (
        'information_schema',
        'performance_schema',
        'mysql',
        'sys'
    )
GROUP BY
    table_schema
ORDER BY
    `Size (MB)` DESC;

-- === PERFORMANCE: Recent Heavy Waits ===
SELECT
    '=== Performance: Recent Heavy Waits ===' AS `--`;

SELECT
    event_name AS `Operation`,
    COUNT_STAR AS `Executions`,
    ROUND(sum_timer_wait / 1000000000, 2) AS `Time (sec)`
FROM
    performance_schema.events_waits_summary_global_by_event_name
WHERE
    sum_timer_wait > 0
ORDER BY
    sum_timer_wait DESC
LIMIT
    10;

-- === ACTIVE CONNECTIONS ===
SELECT
    '=== Active Connections ===' AS `--`;

SELECT
    user AS `User`,
    COUNT(*) AS `Session Count`
FROM
    information_schema.processlist
GROUP BY
    user;

SELECT
    user AS `User`,
    host AS `Host`,
    db AS `Database`,
    command AS `Command`,
    time AS `Time (sec)`,
    state AS `State`,
    info AS `Query`
FROM
    information_schema.processlist
WHERE
    command != 'Sleep'
    OR time > 0;

-- === LOG SETTINGS ===
SELECT
    '=== Log Settings ===' AS `--`;

SHOW VARIABLES LIKE 'log_error';

SHOW VARIABLES LIKE 'slow_query_log';

SHOW VARIABLES LIKE 'long_query_time';

-- === ENABLE SLOW LOG IF DISABLED (run once) ===
SET
    @enable_slow_log = (
        SELECT
            IF(
                (
                    SELECT
                        VARIABLE_VALUE
                    FROM
                        performance_schema.global_variables
                    WHERE
                        VARIABLE_NAME = 'slow_query_log'
                ) = 'OFF',
                'SET GLOBAL slow_query_log = ON; SET GLOBAL long_query_time = 2; SELECT ''Slow log enabled'' AS `Status`;',
                'SELECT ''Slow log already ON'' AS `Status`;'
            )
    );

PREPARE stmt
FROM
    @enable_slow_log;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

-- === INNODB STATUS (Run separately with --vertical) ===
-- WARNING: \G not supported in script mode. Execute manually:
-- mysql -u root -p --vertical -e "SHOW ENGINE INNODB STATUS"
-- === END ===
SELECT
    '=== Report Complete ===' AS `--`;