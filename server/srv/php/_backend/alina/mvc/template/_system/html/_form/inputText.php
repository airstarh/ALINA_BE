<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\Str;

// echo '<pre>';
// var_export($data, 0);
// echo '</pre>';
$disabled    = false;
$type        = $data->type;
$name        = $data->name;
$value       = $data->value;
$placeholder = @$data->placeholder ?: '';
$_name       = substr(strip_tags($name), 0, 200);
$_value      = substr(strip_tags(Data::stringify($value)), 0, 200);
if ($name === 'password') {
    $value = '';
    $type  = 'password';
}

if ($name === 'id') {
    $type = 'hidden';
}

//if ($name === 'created_at') {
//    $value = '';
//    $type  = 'date';
//}
?>
<div class="form-group mt-3">
    <label class="d-block">
        <?= htmlAlias::elBootstrapBadge([
            'title' => $_name,
            'badge' => $_value,
        ]) ?>
        <?php if ($type === 'textarea') { ?>
            <textarea
                    <?= $disabled ? 'disabled' : '' ?>
                    name="<?= $name ?>"
                    class="form-control"
                    rows="5"
            ><?= $value ?></textarea>
        <?php } else { ?>
            <input
                <?= $disabled ? 'disabled' : '' ?>
                    type="<?= $type ?>"
                    name="<?= $name ?>"
                    value="<?= $value ?>"
                    placeholder="<?= $placeholder ?>"
                    class="
                        <?= Str::ifContains($name, 'date') ? 'datepicker' : '' ?>
                        form-control
                    "
            >
        <?php } ?>
    </label>
</div>
