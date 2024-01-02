<?php
/** @var array $data */
$breadcrumbs = $data;
?>

<div class="breadcrumbs">
    <?php foreach ($breadcrumbs as $i => $item): ?>

        <div class="bc-item" style="margin-left: <?= $i * 1.2 ?>vw">
            <a href="<?= $item['href'] ?>"
               class="btn btn-sm btn-secondary m-1 text-left"
            ><?= $item['txt'] ?></a>

            <?= ___($item['table']) ?>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .breadcrumbs {

    }

    .breadcrumbs .bc-item{
        /*float:left;*/
        /*font-size: 0.5em;*/
    }

    .breadcrumbs,
    .breadcrumbs .btn-sm {
        font-size: 0.7rem;
    }
</style>
