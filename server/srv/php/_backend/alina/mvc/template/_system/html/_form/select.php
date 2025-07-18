<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;

$multiple    = (bool)(@$data->multiple);
$disabled    = (bool)(@$data->disabled);
$required    = (bool)(@$data->required);
$name        = !empty($multiple) ? "{$data->name}[]" : $data->name;
$value       = $data->value;
$options     = $data->options;
$placeholder = @$data->placeholder ?: '';
$showLabel   = @$data->showLabel ?: false;
#####
$_options = [];
foreach ($options as $i => $v) {
    $ind = $v;
    $val = Data::stringify($v);
    if (Data::isIterable($v)) {
        $ind = $i;
        $_v  = (object)$v;
        if (property_exists($_v, 'id')) {
            $ind = $_v->id;
        }
    }
    $_options[$ind] = $val;
}
$_options_keys = array_keys($_options);
#####
$_value = [];
foreach ($value as $i => $v) {
    $ind = $v;
    $val = Data::stringify($v);
    if (Data::isIterable($v)) {
        $ind = $i;
        $_v  = (object)$v;
        if (property_exists($_v, 'id')) {
            $ind = $_v->id;
        }
    }
    $_value[$ind] = $val;
}
$_value_keys = array_keys($_value);
#####
?>

<div class="form-group mt-3">

    <label class="d-block">
        <?= htmlAlias::elBootstrapBadge([
            'title' => $name,
            'badge' => count((array)$value) . ' of ' . count((array)$options),
        ]) ?>
        <select
                name="<?= $name ?>"
                class="form-control"
            <?= $multiple ? 'multiple' : '' ?>
            <?= $disabled ? 'disabled' : '' ?>
            <?= $required ? 'required' : '' ?>
            <?php if ($multiple) { ?>
                size="<?= count($_options) + 2 ?>"
            <?php } ?>
        >
            <option value=""
                <?= $multiple ? 'disabled' : '' ?>
            ><?= $placeholder ?></option>
            <?php foreach ($_options as $i => $v) { ?>
                <option value="" style="font-size:10px;" disabled>&nbsp;</option>
                <option
                        value="<?= $i ?>"
                    <?= in_array($i, $_value_keys) ? 'selected' : '' ?>
                ><?= $v ?></option>

            <?php } ?>
        </select>
    </label>
</div>
