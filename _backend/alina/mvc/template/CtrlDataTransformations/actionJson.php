<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

?>
<div id="array-serializer">
    <form action="" method="post" enctype="multipart/form-data">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'JSON string',
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
    <!--##################################################-->
    <!--##################################################-->
    <!--##################################################-->
    <div class="mt-3">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'RESULT',
            'badge' => 'strRes',
        ]) ?>
        <textarea class="form-control w-100" rows="10"><?= $data->strRes ?></textarea>
    </div>
    <div class="mt-3">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'RESULT beautified JSON',
            'badge' => 'strRes',
        ]) ?>
        <textarea class="form-control"
                  rows="30"><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->strRes) ?></textarea>
    </div>
    <!--##################################################-->
    <!--##################################################-->
    <!--##################################################-->
    <div>
        <div><h3>Total Changes [tCount]: <?= $data->tCount ?></h3></div>
        <div class="row">
            <div class="col">
                <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                    'title' => 'Was',
                    'badge' => mb_strlen($data->strSource),
                ]) ?>
            </div>
            <div class="col">
                <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                    'title' => 'Is',
                    'badge' => mb_strlen($data->strRes),
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <?php
                echo '<pre>';
                print_r($data->mxdJsonDecoded);
                echo '</pre>';
                ?>
            </div>
            <div class="col-6">
                <?php
                echo '<pre>';
                print_r($data->mxdResJsonDecoded);
                echo '</pre>';
                ?>
            </div>
        </div>
    </div>
</div>
