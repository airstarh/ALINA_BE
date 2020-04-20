<?php
/** @var $data stdClass */
?>
<!--<textarea name="" id="" rows="5" class="form-control">--><? //= var_export($data, 1) ?><!--</textarea>-->
<?php foreach ($data->tale as $tale) { ?>
    <!-- ##################################################-->
    <div>
        <div class="row no-gutters">
            <h2 class="notranslate col" lang="<?= $tale->lang ?>">
                <a href="/tale/upsert/<?= $tale->id ?>" target="_blank" class="btn btn-block btn-secondary text-left">
                    <?= $tale->header ?>
                </a>
            </h2>
        </div>
        <!-- ##################################################-->
        <div class="row no-gutters">
            <div class="col-auto">
                <div class="fixed-height-150px">
                    <a href="/auth/profile/<?= $tale->owner_id ?>">
                        <img src="<?= $tale->owner_emblem ?>" width="150px" class="rounded-circle">
                    </a>
                </div>
            </div>
            <div class="notranslate col text-right">
                <a href="/#/auth/profile/<?= $tale->owner_id ?>"
                   class="btn btn-sm btn-primary text-left text-break mb-1"
                ><?= $tale->owner_firstname ?> <?= $tale->owner_lastname ?>
                </a>
                <br>
                <a href="/#/tale/upsert/<?= $tale->id ?>"
                   class="btn btn-sm btn-info text-left mb-1">
                    <?= \alina\utils\DateTime::toHumanDate($tale->publish_at) ?>
                </a>
                <br>
            </div>
        </div>
        <!-- ##################################################-->
        <div class="notranslate ck ck-content"><?= $tale->body ?></div>
    </div>
    <div class="clearfix mb-4"></div>
<?php } ?>
