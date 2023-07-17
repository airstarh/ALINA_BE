<?php
/** @var $data stdClass */
?>
<div class="container-fluid">
    <div class="mt-5 ck-content">
        <h1>Controllers' Index</h1>
        <div>
            <?php foreach ($data as $indexFile => $file) { ?>
                <div class="mt-5">&nbsp;</div>
                <div class="row">
                    <div class="col-3">
                        <div><?= $file['header'] ?></div>
                    </div>
                    <div class="col">
                        <?php foreach ($file['url'] as $indexUrl => $url) { ?>
                            <div class="row mb-3">
                                <div class="col">
                                    <div>
                                        <a
                                            href="<?= $url ?>"
                                            target="_blank"
                                        ><?= $url ?>
                                        </a>
                                    </div>
                                    <div>
                                        <a
                                            href="<?= \alina\utils\Html::aRef($url) ?>"
                                            target="_blank"
                                        ><?= \alina\utils\Html::aRef($url) ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="text" value="<?= $file['header'] ?>" class="form-control">
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<div class="ck-content">
    <pre><?= \alina\utils\Data::hlpGetBeautifulJsonString($data) ?></pre>
</div>
