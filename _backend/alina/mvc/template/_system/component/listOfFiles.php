<?php
/** @var $data html */

use alina\utils\FS;

?>
<div>
    <?php
    $h1    = $data['h1'];
    $h1_a  = $data['h1_a'];
    $dir   = $data['dir'];
    $class = $data['class'];
    $list  = FS::dirToRelativeUrlList($dir);
    ?>
    <?php if ($h1_a) { ?>
        <h1><a href="<?= $h1_a ?>"><?= $h1 ?></a></h1>
    <?php } else { ?>
        <h1><?= $h1 ?></h1>
    <?php } ?>

    <?php foreach ($list as $i => $item) { ?>
        <div class="row">
            <div class="col">
                <a
                    class="btn <?= $class ?>"
                    href="<?= $item['link'] ?>"
                ><?= $item['header'] ?></a>
            </div>
            <div class="col">
                <input type="text" value="<?= $item['link'] ?>">
            </div>
            <div class="col">
                <?= $item['description'] ?>
            </div>
        </div>
    <?php } ?>
</div>
