<?php
/** @var $data stdClass */

$name        = $data->name;
$value       = $data->value;
$placeholder = @$data->placeholder ?: '';

use alina\mvc\view\html as htmlAlias; ?>
<div class="form-group mt-3">
    <?= htmlAlias::elBootstrapBadge([
        'title' => $name,
        'badge' => $value,
    ]) ?>
    <input
        type="text"
        name="<?= $name ?>"
        value="<?= $value ?>"
        placeholder="<?= $placeholder ?>"
        class="form-control"
    >
</div>
