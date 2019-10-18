<?php
/** @var $data stdClass */

$action  = @$data->action ?: '';
$enctype = @$data->enctype ?: 'multipart/form-data';
$source  = $data->source;

use alina\mvc\view\html as htmlAlias;
use alina\utils\Data; ?>
<form action="<?= $action ?>" method="post" enctype="<?= $enctype ?>">
    <?= htmlAlias::elFormStandardButtons([]) ?>
    <?php foreach ($source as $f => $v) { ?>
        <?php if (Data::isIterable($v)) { ?>
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
        <?php } ?>
    <?php } ?>
    <!--#####-->
    <!--#####-->
    <!--#####-->
    <?= htmlAlias::elFormStandardButtons([]) ?>

    elFormStandardButtons
</form>
