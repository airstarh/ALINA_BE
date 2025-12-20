-- SET @tableName = 'wp_options';
-- SET @dbName = 'stage001';
SELECT column_name
FROM information_schema.columns
WHERE table_name = '<?= $data->tableName  ?>'
  AND table_schema = '<?= $data->db  ?>'
ORDER BY column_name;
