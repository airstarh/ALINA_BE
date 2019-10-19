<?php
/** @var $data stdClass */

$multiple    = @$data->multiple ? 'multiple' : '';
$name        = !empty($multiple) ? "{$data->name}[]":$data->name;
$value       = $data->value;
$options     = $data->options;
$placeholder = @$data->placeholder ?: '';

use alina\mvc\view\html as htmlAlias;
use alina\utils\Data; ?>
<div class="form-group mt-3">
    <?= htmlAlias::elBootstrapBadge([
        'title' => $name,
        'badge' => count((array)$value) . ' of ' . count((array)$options),
    ]) ?>
    <select
        name="<?= $name ?>"
        class="form-control"
        <?= $multiple ?>
        <?php if (!empty($multiple)) { ?>
            size="<?= count((array)$options) + 2 ?>"
        <?php } ?>
    >
        <option value=""><?= $placeholder ?></option>
        <?php foreach ($options as $i => $o) { ?>
            <option
                value="<?= $i ?>"
                <?= in_array($i, (array)$value) ? 'selected' : '' ?>
            >(<?= $i ?>) <?= Data::stringify($o) ?></option>
        <?php } ?>
    </select>
</div>
