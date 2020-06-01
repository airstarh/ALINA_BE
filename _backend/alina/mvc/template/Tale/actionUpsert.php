<?php
/** @var $data stdClass */
?>
<div class="container p-0"><!---->
    <div class="row no-gutters mt-2 mb-2">
        <h1 class="notranslate col">
            <a href="/#/tale/upsert/<?= $data->id ?>" class="btn btn-block text-left" style="background-color: #8F2DA8; color: #fff;">
                <?= $data->header ?: '¯_(ツ)_/¯' ?></a></h1>
    </div>
    <div class="row no-gutters">
        <div class="col mx-auto">
            <div class="row no-gutters">
                <div class="col-auto">
                    <div class="fixed-height-150px"><a href="/#/auth/profile/<?= $data->owner_id ?>" class=""><img src="<?= $data->owner_emblem ?>" width="100px" class="rounded-circle"><!----></a></div>
                </div>
                <div class="notranslate col text-right">
                    <a href="/#/auth/profile/<?= $data->owner_id ?>" class="btn btn-sm btn-primary text-left text-break mb-1"><?= $data->owner_firstname ?> <?= $data->owner_lastname ?></a>
                    <br>
                    <a href="/#/tale/upsert/<?= $data->id ?>" aria-current="page" class="btn btn-sm btn-info text-left mb-1 router-link-exact-active router-link-active"><?= \alina\utils\DateTime::toHumanDateTime($data->publish_at) ?></a>
                    <br>
                </div>
            </div>
            <div>
                <div class="row no-gutters">
                    <div class="col">
                        <div class="ck-content">
                            <div class="notranslate">
                                <?= $data->body ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row no-gutters">
                    <a class="col btn"
                       style="background-color: #8F2DA8; color: #fff;"
                       href="/#/tale/upsert/<?= $data->id ?>"
                    >
                        Go to comments
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>