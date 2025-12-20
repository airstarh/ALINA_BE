<?php /** @var $data stdClass */ ?>
SELECT
<?php $arr = [] ?>
<?php array_walk($data->arrColumnsWithoutPk, function ($v, $k) use (&$arr) { $arr[$k] = "`{$v}`"; }); ?>
<?= implode(", \n", $arr) ?>

FROM `<?= $data->tableName ?>`
WHERE `<?= $data->pkName ?>` = :<?= $data->pkName ?>
;
