-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'file';
SET @column_list = 'name_fs, owner_id, entity_id, entity_table';
SET @constraint_name = CONCAT('cns_uniq_', @table_name, '_', REPLACE(@column_list, ', ', '_'));
