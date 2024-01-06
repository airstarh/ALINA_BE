<?php
/**@var $data array */

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
            <h1><?= ___("Fill Work Unit Done") ?></h1>
            <div class="clear">&nbsp;</div>

            <?= (new html)->piece('Pm/_pmBreadCrumbs.php', $breadcrumbs) ?>

            <div>
                <?php foreach ($list as $item): ?>
                    <div class="m-3">
                        <a href="<?= $url ?>/<?= $item->id ?>"
                           class="btn btn-lg text-left bg-black text-white"
                        >
                            <?php if ($listOfTable === 'pm_work'): ?>
                                <?= (new html())->piece('_system/html/_form/table002.php', json_decode($item->name_human)) ?>
                            <?php else: ?>
                                <?= $item->name_human ?>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>


            <d0v>
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
                                </label>

                                <div>
                                    <label>
                                        <span class="d-block"><?= ___('for_date') ?></span>
                                        <input type="date" name="for_date" required>
                                    </label>
                                </div>
                            </div>
                            <?= html::elFormStandardButtons([]) ?>
                        </form>
                    </div>

                    <?php if (!empty($listWorkDone)): ?>
                        <div class="bg-black">
                            <table class="alina-data-table">
                                <thead>
                                <tr>
                                    <th><?= ___("for_date") ?></th>
                                    <th><?= ___("Name") ?></th>
                                    <th><?= ___("amount") ?></th>
                                    <th><?= ___("price_final") ?></th>
                                    <th><?= ___("time_spent") ?></th>
                                </tr>
                                </thead>
                                <?php foreach ($listWorkDone as $k => $v): ?>
                                    <tr>
                                        <td><?= \alina\Utils\DateTime::toHumanDate($v->for_date) ?></td>
                                        <td><?= $v->{'assignee.firstname'} ?> <?= $v->{'assignee.lastname'} ?></td>
                                        <td><?= $v->amount ?></td>
                                        <td><?= $v->price_final ?></td>
                                        <td><?= $v->time_spent ?></td>
                                        <td>
                                            <?php if (AlinaAccessIfAdminOrModeratorOrOwner($v->assignee_id)): ?>
                                                <form action="" method="post">
                                                    <input type="hidden" name="form_id" value="actionFillWorkUnitDone">
                                                    <input type="hidden" name="do" value="delete_pm_work_done">
                                                    <input type="hidden" name="pm_work_done_id" value="<?= $v->id ?>">
                                                    <button type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('<?= ___("Are you sure?") ?>');"
                                                    ><?= ___("Delete") ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <!--region IF WORK ID
                <!--########################################################################################################################-->
            </d0v>

            <!--endregion PAGE-->
            <!--##################################################-->
        </div>
    </div>
</div>

