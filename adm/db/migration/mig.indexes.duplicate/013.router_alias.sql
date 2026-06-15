-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'router_alias';
SET @column_list = 'alias';
SET @constraint_name = CONCAT('cns_uniq_', @table_name, '_', REPLACE(@column_list, ', ', '_'));
