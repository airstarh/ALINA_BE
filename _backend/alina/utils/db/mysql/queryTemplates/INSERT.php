<?php /** @var $data stdClass */ ?>
INSERT INTO `<?= $data->tableName ?>`
(
<?php $arr = [] ?>
<?php array_walk($data->arrColumnsWithoutPk, function ($v, $k) use (&$arr) { $arr[$k] = "`{$v}`"; }); ?>
<?= implode(", \n", $arr) ?>

) VALUES (
<?php $arr = [] ?>
<?php array_walk($data->arrColumnsWithoutPk, function ($v, $k) use (&$arr) { $arr[$k] = ":{$v}"; }) ?>
<?= implode(", \n", $arr) ?>

);
