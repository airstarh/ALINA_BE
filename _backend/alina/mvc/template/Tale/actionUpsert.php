<?php
/** @var $data stdClass */
?>

<div class="row">
    <div class="col">
        <div class="float-left mr-1 fixed-height-150px">
            <a href="<?= $data->owner_emblem ?>" target="_blank">
                <img src="<?= $data->owner_emblem ?>" width="150px" class="rounded-circle">
            </a>
        </div>

        <a href="/auth/profile/<?= $data->owner_id ?>">
            <?= $data->owner_firstname ?>
            <?= $data->owner_lastname ?>
        </a>

        <br>
        <a href="/tale/upsert/<?= $data->id ?>">
            <?= \alina\utils\DateTime::toHumanDateTime($data->publish_at) ?>
        </a>
    </div>
    <div class="col"><h2><?= $data->header ?></h2></div>
</div>
<div class="row">
    <div class="col">
        <div class="ck-content">
            <?= $data->body ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col">
        <div>
        </div>
    </div>
</div>
<div class="clearfix"></div>
<div class="row">
    <a class="col btn btn-secondary"
       href="/#/tale/upsert/<?= $data->id ?>"
    >
        Discussion
    </a>
</div>

