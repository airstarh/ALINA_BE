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
            <div>
                <?php foreach ($list as $item): ?>
                    <div class="m-3">
                        <a href="<?= $url ?>/<?= $item->id ?>"
                           class="btn btn-lg text-left bg-black text-white"
                        ><?= $item->name_human ?></a>
                    </div>
                <?php endforeach; ?>
            </div>


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
                                <th><?= ___("Date") ?></th>
                                <th><?= ___("Name") ?></th>
                                <th><?= ___("amount") ?></th>
                                <th><?= ___("price_final") ?></th>
                                <th><?= ___("time_spent") ?></th>
                            </tr>
                            </thead>
                            <?php foreach ($listWorkDone as $k => $v): ?>
                                <tr>
                                    <td><?= \alina\Utils\DateTime::toHumanDateTime($v->modified_at) ?></td>
                                    <td><?= $v->{'assignee.firstname'} ?> <?= $v->{'assignee.lastname'} ?></td>
                                    <td><?= $v->amount ?></td>
                                    <td><?= $v->price_final ?></td>
                                    <td><?= $v->time_spent ?></td>
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

