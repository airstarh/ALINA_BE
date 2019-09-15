<?php /** @var $data stdClass */ ?>
<?php
$name        = $data->name;
$value       = $data->value;
$options     = $data->options;
$placeholder = @$data->placeholder ?: '';
$label1      = @$data->label1 ?: '';
$label2      = @$data->label2 ?: '';
?>
<div class="form-group mt-3">
    <select name="<?= $name ?>" class="form-control">
        <option value=""><?= $placeholder ?></option>
        <?php foreach ($options as $o) { ?>
            <option
                value="<?= $o ?>"
                <?= $o == $value ? 'selected' : '' ?>
            ><?= $o ?></option>
        <?php } ?>
    </select>
</div>
