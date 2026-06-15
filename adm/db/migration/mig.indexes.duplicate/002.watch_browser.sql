-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'watch_browser';
SET @column_list = 'enc';
SET @constraint_name = CONCAT('cns_uniq_', @table_name, '_', REPLACE(@column_list, ', ', '_'));
