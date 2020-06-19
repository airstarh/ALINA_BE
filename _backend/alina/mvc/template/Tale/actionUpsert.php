<?php
/** @var $data stdClass */
?>
<div class="container p-0"><!---->
    <div class="row no-gutters mt-2 mb-2">
        <div class="col">
            <h1 class="notranslate rounded p-2" style="background-color: #8F2DA8; color: #fff;">
                <a href="/#/tale/upsert/<?= $data->id ?>" class="text-left text-light">
                    <?= $data->header ?: '¯_(ツ)_/¯' ?>
                </a>
                <span>
                    <a href="/#/tale/upsert/<?= $data->id ?>" aria-current="page" class="btn btn-sm btn-info text-left float-right mt-3"><?= \alina\utils\DateTime::toHumanDateTime($data->publish_at) ?></a>
                </span>
                <span class="clearfix"></span>
            </h1>
        </div>
    </div>
    <div class="row no-gutters">
        <div class="col mx-auto">

            <div class="row no-gutters mt-2 mb-2">
                <div class="col-auto">
                    <span class="btn-secondary text-left text-nowrap badge-pill p-2">
                        <a href="/#/auth/profile/<?= $data->owner_id ?>" class="fixed-height-150px">
                            <img src="<?= $data->owner_emblem ?>" width="100px" class="rounded-circle">
                        </a>
                        <a href="/#/auth/profile/<?= $data->owner_id ?>"
                           class="text-light"
                        ><?= $data->owner_firstname ?> <?= $data->owner_lastname ?>
                        </a>
                    </span>
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
                <? if (!empty($data->iframe)) { ?>
                    <div class="mt-3">
                        <iframe src="<?= $data->iframe ?>" frameborder="1" width="100%" height="500px"></iframe>
                    </div>
                <? } ?>
                <div class="mt-3"></div>
                <div class="row no-gutters">
                    <div class="col">
                        <iframe
                                id="AlinaIframe001"
                                class="AlinaIframe"
                                src="/#/tale/upsert/<?= $data->id ?>"
                                width="100%"
                                allowfullscreen
                                frameborder="0"
                                scrolling="no"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>