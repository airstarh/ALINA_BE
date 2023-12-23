<?php /** @var $data stdClass */ ?>
<?php
$title = $data->title;
$badge = substr($data->badge, 0, 50);
?>
<span class="btn btn-dark">
<?= ___($title) ?> <span class="badge badge-light"><?= $badge ?></span>
</span>
