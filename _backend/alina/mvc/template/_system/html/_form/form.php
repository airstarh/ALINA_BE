<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;

$action  = @$data->action ?: '';
$enctype = @$data->enctype ?: 'multipart/form-data';
$model   = $data->model;
$sources = $data->sources;

// echo '<pre>';
// print_r($sources);
// echo '</pre>';

?>
<form action="<?= $action ?>" method="post" enctype="<?= $enctype ?>">
    <?= htmlAlias::elFormStandardButtons([]) ?>
    <?php foreach ($model as $f => $v) { ?>
        <?php if (isset($sources[$f])) { ?>
            <?= htmlAlias::elFormSelect([
                'multiple'    => (isset($sources[$f]['multiple'])) ? $sources[$f]['multiple'] : '',
                'name'        => $f,
                'value'       => (Data::isIterable($v)) ? array_keys((array)$v) : $v,
                'options'     => $sources[$f]['list'],
                'placeholder' => '----------------------',//$f,
            ]) ?>
        <?php } else {
            if (Data::isIterable($v)) { ?>
                <?= htmlAlias::elFormSelect([
                    'multiple'    => 'multiple',
                    'name'        => $f,
                    'value'       => array_keys((array)$v),
                    'options'     => (array)$v,
                    'placeholder' => '',//$f,
                ]) ?>
            <?php } else { ?>
                <?= htmlAlias::elFormInputText([
                    'name'        => $f,
                    'value'       => $v,
                    'placeholder' => '',//$f,
                ]) ?>
            <?php }
        } ?>
    <?php } ?>
    <!--#####-->
    <!--#####-->
    <!--#####-->
    <?= htmlAlias::elFormStandardButtons([]) ?>
</form>
