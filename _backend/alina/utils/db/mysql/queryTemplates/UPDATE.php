<?php /** @var $data stdClass */ ?>
UPDATE `<?= $data->tableName ?>`
SET
<?php $arr = [] ?>
<?php array_walk($data->arrColumnsWithoutPk, function ($v, $k) use (&$arr) { $arr[$k] = "`{$v}` = :{$v}"; }); ?>
<?= implode(", \n", $arr) ?>

WHERE `<?= $data->pkName ?>` = :<?= $data->pkName ?>
;
