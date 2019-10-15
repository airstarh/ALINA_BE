SET @tableName = 'timezone';
SET @dbName = 'alina';
SELECT column_name
FROM information_schema.columns
WHERE table_name = @tableName
      AND table_schema = @dbName