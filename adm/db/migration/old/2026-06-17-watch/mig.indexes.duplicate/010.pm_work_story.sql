-- # # # adm/db/migration/mig.indexes.duplicate/001.watch_ip.sql# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- !!! ATTENTION !!!
-- Changed logic of CNS name : was TOO long name.
-- # # # adm/db/migration/mig.indexes.duplicate/001.watch_ip.sql# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- Устанавливаем переменные: имя таблицы и поля (или несколько полей через запятую)
SET @table_name = 'pm_work_story';
SET @column_list = 'wd_assignee_id, pm_organization_id, pm_department_id, pm_project_id, pm_task_id, pm_subtask_id, pm_work_id, pm_work_done_id';
SET @constraint_name = 'cns_uq_pm_work_story_all';


