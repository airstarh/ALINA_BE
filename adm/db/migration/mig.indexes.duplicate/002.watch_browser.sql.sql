-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'watch_browser';
SET @column_list = 'enc';
SET @constraint_name = CONCAT('cns_uniq_', @table_name, '_', REPLACE(@column_list, ', ', '_'));





-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- 1. Добавляем временный индекс (динамически)
SET @sql1 = CONCAT('ALTER TABLE ', @table_name, ' ADD INDEX idx_temp (', @column_list, ')');
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;





-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- 2. Удаляем дубликаты по указанным полям
-- Используем внутреннее соединение по значениям полей и оставляем запись с минимальным id
SET @sql2 = CONCAT('
    DELETE t1 FROM ', @table_name, ' t1
    INNER JOIN ', @table_name, ' t2
    WHERE (', @column_list, ') = (', REPLACE(@column_list, '`', ''), ')
      AND t2.id < t1.id
');
-- Заменяем запятые в списке полей на AND для сравнения
-- Например: ip = t2.ip AND port = t2.port
SET @where_clause = '';
SELECT
    GROUP_CONCAT(
        CONCAT('t1.`', TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(@column_list, ',', n.n), ',', -1)), '` = t2.`', TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(@column_list, ',', n.n), ',', -1)), '`')
        SEPARATOR ' AND '
    ) INTO @where_clause
FROM
    (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) n
WHERE
    n.n <= 1 + (LENGTH(@column_list) - LENGTH(REPLACE(@column_list, ',', '')));

SET @sql2 = CONCAT('DELETE t1 FROM ', @table_name, ' t1 INNER JOIN ', @table_name, ' t2 WHERE ', @where_clause, ' AND t2.id < t1.id');

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;






-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- 3. Удаляем временный индекс
SET @sql3 = 'ALTER TABLE ' + @table_name + ' DROP INDEX idx_temp';
SET @sql3 = CONCAT('ALTER TABLE ', @table_name, ' DROP INDEX idx_temp');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;





-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- 4. Добавляем уникальное ограничение с использованием BTREE
SET @sql4 = CONCAT('ALTER TABLE ', @table_name, ' ADD CONSTRAINT ', @constraint_name, ' UNIQUE USING BTREE (', @column_list, ')');
PREPARE stmt4 FROM @sql4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- Готово!
SELECT 'Done' || @table_name AS result;
