<?php

use alina\mvc\view\html as htmlAlias;

?>
<div class="mt-5"></div>
<h1>Serialized Data Editor online</h1>
<div class="mt-2"></div>
<div class="ck-content">
    <p>This tool is attended to help PHP developers read and edit serialized data.</p>
    <p>Often CMSes (e.g. WordPress) store settings and other data in a so-called <a href="https://en.wikipedia.org/wiki/Serialization" target="_blank">Serialized format</a>.</p>
    <p>That's awful:</p>
    <ul>
        <li>You cannot simply read it!</li>
        <li>You cannot simply edit it!</li>
        <li>You cannot simply port it somewhere!</li>
    </ul>
    <p>I help you.</p>
    <div class="mt-2"></div>
    <p>It works with arrays and objects and pre-saves initial value-types.</p>
    <p>One disadvantage is here: currently this tool works with standard PHP-built-in arrays and objects only.</p>
</div>
<div class="mt-5"></div>
<div id="array-serializer">
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="form_id" value="<?= $data->form_id ?>">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'Serialized string:',
            'badge' => 'you data-source',
        ]) ?>
        <textarea name="strSource" class="form-control w-100" rows="10"><?= $data->strSource ?></textarea>
        <br>
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'From',
            'badge' => 'I change from...',
        ]) ?>
        <input type="text" name="strFrom" value="<?= $data->strFrom ?>" class="form-control">
        <br>
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'To',
            'badge' => 'I change to...',
        ]) ?>
        <input type="text" name="strTo" value="<?= $data->strTo ?>" class="form-control">

        <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>

    </form>
    <div class="mt-3"></div>
    <div><h3>Total Changes: <?= $data->tCount ?></h3></div>
    <div class="mt-3">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'RESULT',
            'badge' => 'this is what you get after changes',
        ]) ?>
        <textarea class="form-control w-100" rows="10"><?= $data->strRes ?></textarea>
    </div>
    <div class="mt-3">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'RESULT JSON',
            'badge' => 'this is what you get in JSON format',
        ]) ?>
        <textarea class="form-control w-100" rows="10"><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->mixedResControl) ?></textarea>
    </div>
    <div>
        <div class="row">
            <div class="col-6">
                <div>
                    <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                        'title' => 'Source',
                        'badge' => 'var_export of your UnSerialized source-string',
                    ]) ?>
                </div>
                <div>Symbols: <?= mb_strlen($data->strSource) ?></div>
                <div>
                    <?php
                    echo '<pre>';
                    echo htmlentities(var_export($data->mixedSource, 1));
                    echo '</pre>';
                    ?>
                </div>
            </div>
            <div class="col-6">
                <div>
                    <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                        'title' => 'Result',
                        'badge' => 'var_export of your UnSerialized result-string',
                    ]) ?>
                </div>
                <div>Symbols: <?= mb_strlen($data->strRes) ?></div>
                <div>
                    <?php
                    echo '<pre>';
                    echo htmlentities(var_export($data->mixedRes, 1));
                    echo '</pre>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
