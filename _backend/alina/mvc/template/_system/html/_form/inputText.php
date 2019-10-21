<?php
/** @var $data stdClass */
$inputType   = 'text';
$name        = $data->name;
$value       = $data->value;
$placeholder = @$data->placeholder ?: '';
if ($name === 'password') {
    $value     = '';
    $inputType = 'password';
}

use alina\mvc\view\html as htmlAlias;
use alina\utils\Str; ?>
<div class="form-group mt-3">
    <?= htmlAlias::elBootstrapBadge([
        'title' => $name,
        'badge' => $value,
    ]) ?>
    <input
        type="<?= $inputType ?>"
        name="<?= $name ?>"
        value="<?= $value ?>"
        placeholder="<?= $placeholder ?>"
        class="
            <?= Str::ifContains($name, 'date') ? 'datepicker' : '' ?>
            form-control
        "
    >
</div>
