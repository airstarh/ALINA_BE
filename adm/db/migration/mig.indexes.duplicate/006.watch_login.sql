-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'watch_login';
SET @column_list = 'mail, ip, browser_enc';
SET @constraint_name = CONCAT('cns_uniq_', @table_name, '_', REPLACE(@column_list, ', ', '_'));
