<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\Str;

$action     = @$data->action ?: '';
$enctype    = @$data->enctype ?: 'multipart/form-data';
$attributes = $data->model;
$sources    = $data->sources;

?>

<form action="<?= $action ?>" method="post" enctype="<?= $enctype ?>">

    <?= htmlAlias::elFormStandardButtons([]); ?>

    <?php foreach ($attributes as $f => $v): ?>
        <div class="mt-3">&nbsp;</div>
        <?php
        $_f = substr(strip_tags($f), 0, 200);
        $_v = substr(strip_tags(Data::stringify($v)), 0, 200);

        $required = (isset($sources[$f]['required']))
            ? $sources[$f]['required']
            : false;

        $disabled = (isset($sources[$f]['disabled']))
            ? $sources[$f]['disabled']
            : false;

        $type = (isset($sources[$f]['type']))
            ? $sources[$f]['type']
            : 'text';

        if (
            \alina\Utils\Str::startsWith($_f, '_')
            ||
            \alina\Utils\Str::ifContains($_f, '.')
        ) {
            $type = 'readonly';
        }

        if (
            $f === 'created_at'
            ||
            $f === 'modified_at'
        ) {
            $type = 'readonly';
        }

        if (
            $f === 'created_at'
            ||
            $f === 'modified_at'
        ) {
            $v = (!empty($v)) ? \alina\Utils\DateTime::toHumanDateTime($v) : null;
        }

        if (
            \alina\Utils\Str::ifContains($f, 'date')
        ) {
            $type = 'date';
            $v    = (!empty($v)) ? \alina\Utils\DateTime::toHumanDate($v) : null;
        }

        if ($f === 'id') {
            $disabled = true;
        }

        $flagList = isset($sources[$f]['list']);
        $multiple = (isset($sources[$f]['multiple']) && $sources[$f]['multiple']);
        ?>


        <?php if ($flagList): ?>


            <!--##################################################-->
            <!--region SELECT-->

            <?= htmlAlias::elFormSelect([
                'multiple'    => $multiple,
                'disabled'    => $disabled,
                'required'    => $required,
                'name'        => $f,
                'value'       => (Data::isIterable($v)) ? (array)$v : [$v],
                'options'     => $sources[$f]['list'],
                'placeholder' => ___($f),
            ]) ?>
            <!--endregion SELECT-->
            <!--##################################################-->

        <?php elseif (Data::isIterable($v)): ?>


            <!--##################################################-->
            <!--region Simple List-->
            <?= htmlAlias::elBootstrapBadge([
                'title' => $f,
                'badge' => count((array)$v),
            ]) ?>

            <?= (new htmlAlias())->piece('_system/html/_form/table002.php', $v) ?>

            <!--endregion Simple List-->
            <!--##################################################-->


        <?php else: ?>

            <?php if ($type === 'readonly'): ?>

                <div>
                    <?= htmlAlias::elBootstrapBadge([
                        'title' => $_f,
                        'badge' => null,
                    ]) ?>
                </div>

                <?php if (Data::isJsonEncodedObject($v)): ?>
                    <div>
                        <?= (new htmlAlias())->piece('_system/html/_form/table002.php', json_decode($v)) ?>
                    </div>
                <?php else: ?>
                    <ul class="list-group">
                        <li class="list-group-item-dark d-flex justify-content-between align-items-center">
                            <?= $v ?? ___('NO DATA') ?>
                        </li>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <!--##################################################-->
                <!--region Input Text/TextArea-->
                <?= htmlAlias::elFormInputText([
                    'type'        => $type,
                    'required'    => $required,
                    'disabled'    => $disabled,
                    'name'        => $f,
                    'value'       => $v,
                    'placeholder' => ___($f),
                ]) ?>
                <!--endregion Input Text/TextArea-->
                <!--##################################################-->
            <?php endif; ?>
        <?php endif; ?>


        <?php if ($disabled): ?>
            <?php if (Data::isIterable($v)): ?>
                <?php foreach ($v as $value): ?>
                    <input type="hidden" name="<?= $f ?>[]" value="<?= $value->id ?>">
                <?php endforeach; ?>
            <?php else: ?>
                <input type="hidden" name="<?= $f ?>" value="<?= $v ?>">
            <?php endif; ?>
        <?php endif; ?>

    <?php endforeach; ?>

    <input type="hidden" name="form_id" value="actionEditRow">

    <?= htmlAlias::elFormStandardButtons([]) ?>
</form>
