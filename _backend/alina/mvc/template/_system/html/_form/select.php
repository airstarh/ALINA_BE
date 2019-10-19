<?php
/** @var $data stdClass */

$multiple    = @$data->multiple ? 'multiple' : '';
$name        = $data->name;
$value       = $data->value;
$options     = $data->options;
$placeholder = @$data->placeholder ?: '';

use alina\mvc\view\html as htmlAlias;
use alina\utils\Data; ?>
<div class="form-group mt-3">
    <?= htmlAlias::elBootstrapBadge([
        'title' => $name,
        'badge' => count((array) $value) . ' of '. count((array) $options)
    ]) ?>
    <select name="<?= $name ?>" class="form-control" <?= $multiple ?>>
        <option value=""><?= $placeholder ?></option>
        <?php foreach ($options as $i => $o) { ?>
            <option
                value="<?= $i ?>"
                <?= in_array($i, (array) $value) ? 'selected' : '' ?>
            >(<?= $i ?>) <?= Data::stringify($o) ?></option>
        <?php } ?>
    </select>
</div>
