<?php /** @var $data stdClass */ ?>
DELETE FROM `<?= $data->tableName ?>`
WHERE `<?= $data->pkName ?>` = :<?= $data->pkName ?>
;
