<?php
/** @var array $data */
$breadcrumbs = $data;
?>

<div>
    <?php foreach ($breadcrumbs as $i => $item): ?>
        <div style="margin-left: <?= $i * 2 ?>vw">

            <a href="<?= $item['href'] ?>"
               class="btn btn-sm btn-secondary m-1 text-left"
            ><?= $item['txt'] ?></a>

            <?= ___($item['table']) ?>
        </div>
    <?php endforeach; ?>
</div>
