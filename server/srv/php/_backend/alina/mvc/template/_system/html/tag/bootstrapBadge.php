<?php /** @var $data stdClass */ ?>
<?php
$title = $data->title;
$badge = $data->badge;
?>
<span class="btn btn-primary">
<?= $title ?> <span class="badge badge-light"><?= $badge ?></span>
</span>
