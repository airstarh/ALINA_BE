<?php

use alina\mvc\view\html as htmlAlias;

?>
<div id="array-serializer">
    <form action="" method="post" enctype="multipart/form-data">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'Serialized array]:',
            'badge' => 'strSource',
        ]) ?>
        <textarea name="strSource" class="form-control w-100" rows="10"><?= $data->strSource ?></textarea>
        <br>
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'From',
            'badge' => 'strFrom',
        ]) ?>
        <input type="text" name="strFrom" value="<?= $data->strFrom ?>" class="form-control">
        <br>
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'To',
            'badge' => 'strTo',
        ]) ?>
        <input type="text" name="strTo" value="<?= $data->strTo ?>" class="form-control">

        <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>

    </form>

    <div class="mt-3">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'RESULT',
            'badge' => 'strRes',
        ]) ?>
        <textarea class="form-control w-100" rows="10"><?= $data->strRes ?></textarea>
    </div>
    <div class="mt-3">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'RESULT JSON',
            'badge' => 'arrResControl JSON',
        ]) ?>
        <textarea class="form-control w-100" rows="10"><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->arrResControl) ?></textarea>
    </div>
    <div>
        <div><h3>Total Changes [tCount]: <?= $data->tCount ?></h3></div>
        <div class="row">
            <div class="col-6">
                <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                    'title' => 'arrRes',
                    'badge' => 'arrRes',
                ]) ?>
            </div>
            <div class="col-6">
                <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                    'title' => 'arrResControl',
                    'badge' => 'arrResControl',
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <?= mb_strlen($data->strRes) ?>
            </div>
            <div class="col-6">
                <?= mb_strlen($data->strResControl) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <?php
                echo '<pre>';
                print_r($data->arrRes);
                echo '</pre>';
                ?>
            </div>
            <div class="col-6">
                <?php
                echo '<pre>';
                print_r($data->arrResControl);
                echo '</pre>';
                ?>
            </div>
        </div>
    </div>
</div>
