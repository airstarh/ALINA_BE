-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'tag_to_entity';
SET @column_list = 'entity_id, entity_table';
SET @constraint_name = CONCAT('cns_uniq_', @table_name, '_', REPLACE(@column_list, ', ', '_'));
