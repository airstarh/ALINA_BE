-- diag_report.sql
-- Lightweight MySQL Health Check (Safe for Batch Execution)
-- Logs will be handled by Docker + MySQL config

-- === SYSTEM INFO ===
SELECT '=== MySQL System Information ===' AS `--`;

SELECT VERSION() AS `MySQL Version`;

SELECT 
    CAST((SELECT VARIABLE_VALUE FROM performance_schema.global_variables WHERE VARIABLE_NAME = 'innodb_buffer_pool_size') AS UNSIGNED) / 1024 / 1024 / 1024 AS `InnoDB Buffer Pool (GB)`;

SELECT 
    CAST((SELECT VARIABLE_VALUE FROM performance_schema.global_variables WHERE VARIABLE_NAME = 'innodb_log_file_size') AS UNSIGNED) / 1024 / 1024 AS `InnoDB Log File Size (MB)`;

SELECT 
    (SELECT VARIABLE_VALUE FROM performance_schema.global_variables WHERE VARIABLE_NAME = 'innodb_flush_method') AS `Flush Method`;

-- === DATABASE SIZE ===
SELECT '=== Database Size ===' AS `--`;

SELECT 
    table_schema AS `Database`,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS `Size (MB)`
FROM information_schema.tables
WHERE table_schema NOT IN ('information_schema', 'performance_schema', 'mysql', 'sys')
GROUP BY table_schema
ORDER BY `Size (MB)` DESC;

-- === ACTIVE CONNECTIONS ===
SELECT '=== Active Connections ===' AS `--`;

SELECT 
    user AS `User`,
    host AS `Host`,
    db AS `Database`,
    command AS `Command`,
    time AS `Time (sec)`,
    state AS `State`
FROM information_schema.processlist
WHERE command != 'Sleep' OR time > 0;

-- === LOG CONFIGURATION ===
SELECT '=== Log Configuration ===' AS `--`;

SHOW VARIABLES LIKE 'log_error';
SHOW VARIABLES LIKE 'slow_query_log';
SHOW VARIABLES LIKE 'slow_query_log_file';
SHOW VARIABLES LIKE 'long_query_time';

-- === END ===
SELECT '=== Report Complete ===' AS `--`;
