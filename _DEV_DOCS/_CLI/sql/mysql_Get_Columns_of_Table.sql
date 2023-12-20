SET @tableName = 'user';
SET @dbName = 'home.zero';
SELECT *
FROM information_schema.columns
WHERE table_name = @tableName
  AND table_schema = @dbName
ORDER BY ORDINAL_POSITION
;