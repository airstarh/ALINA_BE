SELECT
      TABLE_SCHEMA
    , TABLE_NAME
    , COLUMN_NAME
    , DATA_TYPE
    , CHARACTER_MAXIMUM_LENGTH
    , COLUMN_TYPE
    , COLUMN_KEY
    , EXTRA
    , CHARACTER_SET_NAME
    , COLLATION_NAME
    , PRIVILEGES
FROM information_schema.COLUMNS
WHERE 1
<?php if (property_exists($data, 'db') && !empty($data->db)) { ?>
    AND TABLE_SCHEMA = '<?= $data->db  ?>'
<?php } ?>
<?php if (property_exists($data, 'tableName') && !empty($data->tableName)) { ?>
    AND
        TABLE_NAME = '<?= $data->tableName  ?>'
<?php } ?>
<?php if (property_exists($data, 'col') && !empty($data->col)) { ?>
    AND
        COLUMN_NAME = '<?= $data->col  ?>'
<?php } ?>
;
