-- diag_report.sql
SELECT
    '=== MySQL Системная Информация ===' AS `--`;

SELECT
    VERSION() AS `Версия MySQL`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'innodb_buffer_pool_size'
    ) / 1024 / 1024 / 1024 AS `InnoDB Buffer Pool (ГБ)`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'innodb_log_file_size'
    ) / 1024 / 1024 AS `InnoDB Log File Size (МБ)`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'max_allowed_packet'
    ) / 1024 / 1024 AS `Max Allowed Packet (МБ)`;

SELECT
    (
        SELECT
            VARIABLE_VALUE
        FROM
            performance_schema.global_variables
        WHERE
            VARIABLE_NAME = 'innodb_flush_log_at_trx_commit'
    ) AS `Flush Log at Commit (режим)`;

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

SELECT
    '=== Состояние Дисков и Таблиц ===' AS `--`;

SELECT
    table_schema AS `База данных`,
    COUNT(*) AS `Кол-во таблиц`
FROM
    information_schema.tables
GROUP BY
    table_schema;

SELECT
    table_schema AS `База данных`,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS `Размер (МБ)`
FROM
    information_schema.tables
GROUP BY
    table_schema
ORDER BY
    `Размер (МБ)` DESC;

SELECT
    '=== Производительность: Последние Операции ===' AS `--`;

SELECT
    event_name AS `Операция`,
    COUNT_STAR AS `Выполнено`,
    ROUND(sum_timer_wait / 1000000000, 2) AS `Время (сек)`
FROM
    performance_schema.events_waits_summary_global_by_event_name
WHERE
    sum_timer_wait > 0
ORDER BY
    sum_timer_wait DESC
LIMIT
    10;

SELECT
    '=== Активные Подключения ===' AS `--`;

SELECT
    user AS `Пользователь`,
    COUNT(*) AS `Кол-во сессий`
FROM
    information_schema.processlist
GROUP BY
    user;

SELECT
    user AS `Пользователь`,
    host AS `Хост`,
    db AS `База`,
    command AS `Команда`,
    time AS `Время (сек)`,
    state AS `Состояние`
FROM
    information_schema.processlist
WHERE
    command != 'Sleep'
    OR time > 0;

SELECT
    '=== InnoDB Статус ===' AS `--`;

SHOW ENGINE INNODB STATUS \ G
SELECT
    '=== Параметры Логов ===' AS `--`;

SHOW VARIABLES LIKE 'log_error';

SHOW VARIABLES LIKE 'slow_query_log';

SHOW VARIABLES LIKE 'long_query_time';

SELECT
    '=== Готово ===' AS `--`;