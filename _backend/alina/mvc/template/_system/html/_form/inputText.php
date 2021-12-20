<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Str;

// echo '<pre>';
// var_export($data, 0);
// echo '</pre>';

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
?>
<div class="form-group mt-3">
    <?= htmlAlias::elBootstrapBadge([
        'title' => $_name,
        'badge' => $_value,
    ]) ?>
    <?php if ($type === 'textarea') { ?>
        <textarea
                name="<?= $name ?>"
                class="form-control"
                rows="5"
        ><?= $value ?></textarea>
    <?php } else { ?>
        <input
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
</div>
