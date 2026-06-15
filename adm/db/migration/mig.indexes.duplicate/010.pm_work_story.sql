-- # # # adm/db/migration/mig.indexes.duplicate/001.watch_ip.sql# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'watcpm_work_storyh_ip';
SET @column_list = 'wd_assignee_id, pm_organization_id, pm_department_id, pm_project_id, pm_task_id, pm_subtask_id, pm_work_id, pm_work_done_id';
SET @constraint_name = CONCAT('cns_uniq_', @table_name, '_', REPLACE(@column_list, ', ', '_'));
