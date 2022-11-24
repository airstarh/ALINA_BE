<?php
/** @var $data stdClass */
?>
<div class="container p-0">
    <!-- -->
    <!-- -->
    <!-- -->
    <?php if ($data->is_header_hidden != 1) { ?>
        <div class="row no-gutters">
            <div class="col" style="position: relative">
                <h1 class="notranslate m-0 p-3 text-left rounded alina-tale-header corporate-bg-and-text">
                    <a
                        href="/#/tale/upsert/<?= $data->id ?>"
                        class="m-0"
                    ><?= $data->header ?: '¯_(ツ)_/¯' ?></a>
                </h1>
                <?php if ($data->is_date_hidden != 1) { ?>
                    <div style="position: absolute; right: 1%; bottom: -1rem; z-index: 10;">
                        <a
                            href="/#/tale/upsert/<?= $data->id ?>"
                            class="btn btn-sm btn-dark text-left"
                        ><?= \alina\utils\DateTime::toHumanDateTime($data->publish_at) ?></a>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <!-- -->
    <!-- -->
    <!-- -->
    <?php if ($data->is_avatar_hidden != 1) { ?>
        <div class="mt-2 mb-2">&nbsp;</div>
        <div class="row no-gutters">
            <div class="col-auto">
                    <span class="btn-secondary text-left text-nowrap badge-pill p-2">
                        <a href="/#/auth/profile/<?= $data->owner_id ?>" class="fixed-height-150px">
                            <img src="<?= $data->owner_emblem ?>" width="100px" class="rounded-circle">
                        </a>
                        <a
                            href="/#/auth/profile/<?= $data->owner_id ?>"
                            class="text-light"
                        ><?= $data->owner_firstname ?> <?= $data->owner_lastname ?>
                        </a>
                    </span>
            </div>
        </div>
    <?php } ?>
    <!-- -->
    <!-- -->
    <!-- -->
    <div class="mt-1">&nbsp;</div>
    <div class="row no-gutters">
        <div class="col mx-auto">
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

                <? if (!empty($data->body_free)) { ?>
                    <div class="mt-3">
                        <div><?= $data->body_free ?></div>
                    </div>
                <? } ?>

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
