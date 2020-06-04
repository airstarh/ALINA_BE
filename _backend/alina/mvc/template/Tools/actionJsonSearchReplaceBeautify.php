<?php
/** @var $data stdClass */

use alina\GlobalRequestStorage;
use alina\mvc\view\html as htmlAlias;

?>
<div>
    <div class="mt-5"></div>
    <h1><?= GlobalRequestStorage::obj()->get('pageTitle') ?></h1>
    <div class="mt-2"></div>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="form_id" value="<?= $data->form_id ?>">
        <?= htmlAlias::elBootstrapBadge([
            'title' => 'JSON string',
            'badge' => 'your data-source',
        ]) ?>
        <textarea name="strSource" class="form-control w-100" rows="10"><?= $data->strSource ?></textarea>
        <br>
        <?= htmlAlias::elBootstrapBadge([
            'title' => 'From',
            'badge' => 'I change from...',
        ]) ?>
        <input type="text" name="strFrom" value="<?= $data->strFrom ?>" class="form-control">
        <br>
        <?= htmlAlias::elBootstrapBadge([
            'title' => 'To',
            'badge' => 'I change to...',
        ]) ?>
        <input type="text" name="strTo" value="<?= $data->strTo ?>" class="form-control">

        <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>

    </form>
    <!-- ##################################################-->
    <div class="mt-5"></div>
    <div class="display-4">Total Changes: <?= $data->tCount ?></div>
    <div class="mt-3">
        <?= htmlAlias::elBootstrapBadge([
            'title' => 'RESULT',
            'badge' => 'this is what you get after changes',
        ]) ?>
        <textarea class="form-control w-100" rows="10"><?= $data->strRes ?></textarea>
    </div>
    <div class="mt-5">
        <?= htmlAlias::elBootstrapBadge([
            'title' => 'RESULT beautified JSON',
            'badge' => 'well-formatted string',
        ]) ?>
        <textarea class="form-control"
                  rows="30"><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->strRes) ?></textarea>
    </div>
    <!-- ##################################################-->
    <div class="mt-5">
        <div class="row">
            <div class="col-6">
                <div>
                    <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                        'title' => 'Was',
                        'badge' => mb_strlen($data->strSource),
                    ]) ?>
                </div>
                <div>
                    <?php
                    echo '<pre>';
                    echo htmlentities(var_export($data->mxdJsonDecoded, 1));
                    echo '</pre>';
                    ?>
                </div>
            </div>
            <div class="col-6">
                <div>
                    <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                        'title' => 'Is',
                        'badge' => mb_strlen($data->strRes),
                    ]) ?>
                </div>
                <div>
                    <?php
                    echo '<pre>';
                    echo htmlentities(var_export($data->mxdResJsonDecoded, 1));
                    echo '</pre>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
