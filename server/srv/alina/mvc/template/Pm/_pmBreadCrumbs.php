<?php
/** @var array $data */

use alina\mvc\View\html;

$breadcrumbs = $data;
?>

<div class="alina-pm-breadcrumbs">
    <?php foreach ($breadcrumbs as $i => $item): ?>

        <div class="bc-item"
            <?php if ($item['table'] !== 'pm_work'): ?>
                style="margin-left: <?= $i * 1.2 ?>vw"
            <?php endif; ?>
        >
            <a href="<?= $item['href'] ?>"
               class="btn btn-sm btn-secondary m-1 text-left"
            >
                <?php if ($item['table'] === 'pm_work'): ?>
                    <?= (new html())->piece('_system/html/_form/table002.php', json_decode($item['txt'])) ?>
                <?php else: ?>
                    <?= $item['txt'] ?>
                <?php endif; ?>
            </a>

            <?= ___($item['table']) ?>
        </div>
    <?php endforeach; ?>
</div>