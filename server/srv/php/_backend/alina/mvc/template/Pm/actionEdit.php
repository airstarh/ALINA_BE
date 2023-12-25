<?php
/**@var $data array */

use alina\mvc\Controller\AdminDbManager;
use alina\mvc\View\html;

$list         = $data['list'];
$listOfTable  = $data['listOfTable'];
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
            <h1><?= ___("Edit Structure") ?></h1>
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
            <!--region LIST -->
            <?php if (!empty($list)): ?>
                <div>


                    <?php if (in_array($listOfTable, ['pm_task', 'pm_subtask'])): ?>
                        <form action="" id="order_in_view" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="form_id" value="order_in_view">
                            <input type="hidden" name="do" value="order_in_view">
                            <input type="hidden" name="model" value="<?= $listOfTable ?>">
                            <button type="submit" form="order_in_view" class="btn btn-sm btn-primary"><?= ___("Save New Order") ?></button>
                        </form>
                    <?php endif; ?>


                    <?php foreach ($list as $item): ?>
                        <div class="mt-3 mb-3">
                            <div class="row no-gutter">
                                <?php if (in_array($listOfTable, ['pm_task', 'pm_subtask'])): ?>
                                    <div class="col-2">
                                        <label>
                                            <input type="text" name="order_in_view[<?= $item->id ?>]" value="<?= $item->order_in_view ?>" form="order_in_view" class="form-control">
                                        </label>
                                    </div>
                                <?php endif; ?>

                                <div class="col">
                                    <a href="<?= $url ?>/<?= $item->id ?>"
                                       class="btn btn-lg text-left bg-black text-white"
                                    ><?= $item->name_human ?></a>

                                    <a href="<?= AdminDbManager::URL_ROW_EDIT ?>/<?= $listOfTable ?>/<?= $item->id ?>"
                                       class="btn btn-sm btn-primary"
                                       target="_blank"
                                    ><?= ___("Edit") ?></a>
                                </div>
                            </div>


                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <!--endregion LIST -->
            <!--########################################################################################################################-->


            <!--########################################################################################################################-->
            <!--region IF WORK ID-->
            <?php if ($mWork->id): ?>
                <div class="mt-5 mb-5">
                    <form action="" method="post">
                        <input type="hidden" name="form_id" value="actionFillWorkUnitDone">
                        <input type="hidden" name="do" value="insert_pm_work_done">
                        <input type="hidden" name="pm_work_id" value="<?= $mWork->id ?>">

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

                <?php if (!empty($listWorkDone)): ?>
                    <div class="bg-black">
                        <table class="alina-data-table">
                            <thead>
                            <tr>
                                <th></th>
                            </tr>
                            </thead>
                            <?php foreach ($listWorkDone as $k => $v): ?>
                                <tr>
                                    <td><?= $v->id ?></td>
                                    <td><?= $v->{'assignee.firstname'} ?> <?= $v->{'assignee.lastname'} ?></td>
                                    <td><?= $v->amount ?></td>
                                    <td><?= \alina\Utils\DateTime::toHumanDateTime($v->modified_at) ?></td>
                                    <td>
                                        <form action="" method="post">
                                            <input type="hidden" name="form_id" value="actionFillWorkUnitDone">
                                            <input type="hidden" name="do" value="delete_pm_work_done">
                                            <input type="hidden" name="pm_work_done_id" value="<?= $v->id ?>">
                                            <button type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('<?= ___("Are you sure?") ?>');"
                                            ><?= ___("Delete") ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <!--region IF WORK ID
            <!--########################################################################################################################-->


            <!--endregion PAGE-->
            <!--##################################################-->
        </div>
    </div>
</div>

