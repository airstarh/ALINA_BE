<?php /** @var stdClass $data */ ?>
<?php
$title = $data->title;
$badge = substr((string) $data->badge, 0, 50);
?>
<span class="btn btn-dark">
<?= ___($title) ?> <span class="badge badge-light"><?= $badge ?></span>
</span>
