<?php
/** @var $data stdClass */

use alina\GlobalRequestStorage;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data as DataAlias;

?>
<div>
    <div class="mt-5"></div>
    <h1><?= GlobalRequestStorage::obj()->get('pageTitle') ?>
        <button class="btn btn-secondary" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
            See the description
        </button>
    </h1>
    <div class="ck-content collapse" id="collapseExample">
        <div class="mt-2"></div>
        <p>This tool is attended to help PHP developers read and edit JSON data.</p>
        <div class="mt-2"></div>
        <p><b>Solution</b></p>
        <div class="mt-2"></div>

        <ul class="todo-list">
            <li><label class="todo-list__label"><input type="checkbox" disabled="" checked=""><span class="todo-list__label__description">Pre-saves numeric data-types, when possible.</span></label></li>
            <li><label class="todo-list__label"><input type="checkbox" disabled="" checked=""><span class="todo-list__label__description">Properly works with nested serialized data.</span></label></li>
            <li><label class="todo-list__label"><input type="checkbox" disabled="" checked=""><span class="todo-list__label__description">Properly works with nested JSONs: Searches-Replaces only values but never parameter names.</span></label></li>
            <li><label class="todo-list__label"><input type="checkbox" disabled="" checked=""><span class="todo-list__label__description">Supports unicode.</span></label></li>
        </ul>

        <div class="mt-2"></div>
        <p><b>Disadvantages are planned to fix</b></p>
        <div class="mt-2"></div>

        <ul class="todo-list">
            <li><label class="todo-list__label"><input type="checkbox" disabled=""><span class="todo-list__label__description">Currently the tool works with standard PHP-built-in arrays and objects only.</span></label></li>
            <li><label class="todo-list__label"><input type="checkbox" disabled=""><span class="todo-list__label__description">Does not handle booleans.</span></label></li>
        </ul>

        <p>Just copy-paste the test data below and try to play with it!</p>
        <pre><code class="language-plaintext">{
    "p0": true,
    "p1": 2020.5,
    "p2": 2020,
    "p3": "2020",
    "p4": "PARAMETER&amp;amp;amp;amp;amp;amp;amp;amp;PARAMETER",
    "p5": "http://site.com/page?lala[]=123&amp;amp;amp;amp;amp;amp;amp;a=буква",
    "p6": "O:8:\"stdClass\":3:{s:5:\"index\";s:4:\"val1\";s:6:\"index2\";s:4:\"2020\";s:6:\"index3\";s:4:\"val3\";}",
    "p7": "Русские буквы",
    "p8": [
        2020,
        "String",
        2020.5
    ],
    "p9": {
        "sub1": "2020",
        "sub2": 2020,
        "sub3": 2020.5
    },
    "p10": "{\"jsonPar2020\":2020}"
}</code></pre>

    </div>
</div>
<div>
    <div class="mt-2"></div>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="form_id" value="<?= $data->form_id ?>">
        <?= htmlAlias::elBootstrapBadge([
            'title' => 'JSON string',
            'badge' => 'your data-source',
        ]) ?>
        <textarea name="strSource" class="form-control w-100" rows="10"><?= htmlentities($data->strSource) ?></textarea>
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
        <textarea class="form-control w-100" rows="10"><?= htmlentities($data->strRes) ?></textarea>
    </div>
    <div class="mt-5">
        <?= htmlAlias::elBootstrapBadge([
            'title' => 'RESULT beautified JSON',
            'badge' => 'well-formatted string',
        ]) ?>
        <textarea class="form-control" rows="30"><?= htmlentities(DataAlias::hlpGetBeautifulJsonString($data->strRes)) ?></textarea>
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
<div>
    <iframe
            id="AlinaIframe001"
            class="AlinaIframe"
            src="/#/tale/upsert/523"
            width="100%"
            allowfullscreen
            frameborder="0"
            scrolling="no"
    ></iframe>
</div>