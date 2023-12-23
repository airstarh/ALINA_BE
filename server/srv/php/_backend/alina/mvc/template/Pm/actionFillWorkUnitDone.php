<?php
/**@var $data array */

use alina\mvc\View\html;

$list         = $data['list'];
$url          = $data['url'];
$breadcrumbs  = $data['breadcrumbs'];
$mWork        = $data['mWork'];
$listWorkDone = $data['listWorkDone'];
?>
<div class="container">
    <div class="row">
        <div class="col">
            <!--##################################################-->
            <!--region PAGE-->

            <div class="clear">&nbsp;</div>
            <h1><?= ___("Fill Work Unit Done") ?></h1>
            <div class="clear">&nbsp;</div>

            <div>

                <?php foreach ($breadcrumbs as $i => $item): ?>
                    <div style="margin-left: <?= $i * 2 ?>vw">
                        <a href="<?= $item['href'] ?>"
                           class="btn btn-sm btn-secondary m-2 d-block text-left"
                        ><?= $item['txt'] ?></a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!--########################################################################################################################-->
            <!--region IF WORK ID-->
            <?php if ($mWork->id): ?>
                <div class="mt-5 mb-5">
                    <form action="" method="post">
                        <input type="hidden" name="pm_work_id" value="<?= $mWork->id ?>">
                        <input type="hidden" name="form_id" value="<?= $mWork->id ?>">

                        <div class="text-center">
                            <label>
                                <span><?= ___("totally:") ?></span
                                ><input type="number"
                                        step="any"
                                        name="amount"
                                        required
                                        class="text-center p-3"
                                        style="++font-size:30pt"
                                ><span><?= ___("doodahs") ?></span>
                            </label></div>
                        <?= html::elFormStandardButtons([]) ?>
                    </form>
                </div>

                <div>
                    <?php foreach ($listWorkDone as $k => $v): ?>
                        <div><?= $v->id ?></div>
                        <div><?= $v->assignee_id ?></div>
                        <div><?= $v->amount ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <!--region IF WORK ID
            <!--########################################################################################################################-->

            <div>
                <?php foreach ($list as $item): ?>
                    <div class="m-3">
                        <a href="<?= $url ?>/<?= $item->id ?>"
                           class="btn btn-lg text-left bg-black text-white"
                        ><?= $item->name_human ?></a>
                    </div>
                <?php endforeach; ?>
            </div>


            <!--endregion PAGE-->
            <!--##################################################-->
        </div>
    </div>
</div>

