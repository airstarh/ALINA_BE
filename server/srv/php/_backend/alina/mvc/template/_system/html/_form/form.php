<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\Str;

$action  = @$data->action ?: '';
$enctype = @$data->enctype ?: 'multipart/form-data';
$model   = $data->model;
$sources = $data->sources;

?>
<form action="<?= $action ?>" method="post" enctype="<?= $enctype ?>">
    <?= htmlAlias::elFormStandardButtons([]) ?>
    <?php foreach ($model as $f => $v) { ?>
        <?php
        $_f = substr(strip_tags($f), 0, 200);
        $_v = substr(strip_tags(Data::stringify($v)), 0, 200);
        ?>
        <!--##################################################-->
        <!--region SELECT-->
        <?php if (array_key_exists($f, $sources) && array_key_exists('list', $sources[$f])) { ?>
            <?= htmlAlias::elFormSelect([
                'multiple'    => (isset($sources[$f]['multiple'])) ? $sources[$f]['multiple'] : '',
                'name'        => $f,
                'value'       => (Data::isIterable($v)) ? (array)$v : [$v],
                'options'     => $sources[$f]['list'],
                'placeholder' => '¯\_(ツ)_/¯',//$f,
            ]) ?>
            <!--endregion SELECT-->
            <!--##################################################-->
            <!--region Simple List-->
        <?php } elseif (Data::isIterable($v)) { ?>
            <div class="form-group mt-3">
                <?= htmlAlias::elBootstrapBadge([
                    'title' => $f,
                    'badge' => count((array)$v),
                ]) ?>
                <ul class="list-group">
                    <?php foreach ($v as $i => $d) { ?>
                        <li class="list-group-item-dark d-flex justify-content-between align-items-center">
                            (<?= $i ?>) <?= Data::stringify($d) ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
            <!--endregion Simple List-->
            <!--##################################################-->
            <!--region Input Text-->
        <?php } else { ?>
            <?php
            $type = (isset($sources[$f]) && array_key_exists('type', $sources[$f])) ? $sources[$f]['type'] : 'text';
            ?>
            <?php if ($type === 'readonly') { ?>
                <div>READ ONLY</div>
                <?= $_f ?>
                <br>
                <?= $_v ?>
            <?php } else { ?>
                <?= htmlAlias::elFormInputText([
                    'type'        => $type,
                    'name'        => $f,
                    'value'       => $v,
                    'placeholder' => '',//$f,
                ]) ?>

            <?php } ?>
        <?php } ?>
        <!--endregion Input Text-->
        <!--##################################################-->
    <?php } ?>
    <input type="hidden" name="form_id" value="actionEditRow">
    <?= htmlAlias::elFormStandardButtons([]) ?>
</form>
