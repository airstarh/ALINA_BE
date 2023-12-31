<?php
/**@var $data array */

use alina\mvc\Controller\AdminDbManager;
use alina\mvc\Controller\Pm;
use alina\mvc\View\html;

$list         = $data['list'];
$listOfTable  = $data['listOfTable'];
$url          = $data['url'];
$breadcrumbs  = $data['breadcrumbs'];
$mWork        = $data['mWork'];
$listWorkDone = $data['listWorkDone'];
$userList     = $data['userList'];
?>
<div class="container">
    <div class="row">
        <div class="col">
            <!--##################################################-->
            <!--region PAGE-->

            <div class="clear">&nbsp;</div>
            <h1>
                <?= ___("Edit Structure") ?>

                <a href="?"
                   class="btn btn-lg btn-warning"
                ><?= ___('Reload page') ?></a>

                <a href="<?= Pm::URL_EDIT ?>"
                   class="btn btn-lg btn-success"
                ><?= ___('Start from the scratch') ?></a>
            </h1>
            <div class="clear">&nbsp;</div>

            <div>

                <?php foreach ($breadcrumbs as $i => $item): ?>
                    <div style="margin-left: <?= $i * 2 ?>vw">
                        <?= ___($item['table']) ?>:
                        <a href="<?= $item['href'] ?>"
                           class="btn btn-sm btn-secondary m-2 text-left"
                        ><?= $item['txt'] ?></a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!--########################################################################################################################-->
            <!--region FORM NEW-->
            <?php if (in_array($listOfTable, ['pm_organization', 'pm_department', 'pm_project', 'pm_task', 'pm_subtask'])): ?>
                <form action="" id="new_model" method="post" enctype="multipart/form-data" class="mt-5 mb-5">
                    <h3><?= ___($listOfTable) ?></h3>
                    <input type="hidden" name="form_id" value="new_model">
                    <input type="hidden" name="do" value="new_model">
                    <input type="hidden" name="model" value="<?= $listOfTable ?>">
                    <button type="submit" form="new_model" class="btn btn-sm btn-primary"><?= ___("Create New") ?></button>
                    <!--#####-->
                    <input type="hidden" name="pm_organization_id" value="<?= $data['pm_organization_id'] ?>">
                    <input type="hidden" name="pm_department_id" value="<?= $data['pm_department_id'] ?>">
                    <input type="hidden" name="pm_project_id" value="<?= $data['pm_project_id'] ?>">
                    <input type="hidden" name="pm_task_id" value="<?= $data['pm_task_id'] ?>">
                    <input type="hidden" name="pm_subtask_id" value="<?= $data['pm_subtask_id'] ?>">
                    <!--#####-->

                    <div class="mt-2">
                        <?php if (in_array($listOfTable, ['pm_task', 'pm_subtask'])): ?>
                            <label>
                                <input type="text" name="order_in_view" placeholder="<?= ___('order_in_view') ?>" class="form-control">
                            </label>
                        <?php endif; ?>

                        <label>
                            <input type="text" name="name_human" placeholder="<?= ___('name_human') ?>" required class="form-control">
                        </label>

                        <?php if (in_array($listOfTable, ['pm_department'])): ?>
                            <label>
                                <input type="text" name="price_min" placeholder="<?= ___('price_min') ?>" class="form-control">
                            </label>
                        <?php endif; ?>

                        <?php if (in_array($listOfTable, ['pm_project'])): ?>
                            <label>
                                <input type="text" name="price_multiplier" placeholder="<?= ___('price_multiplier') ?>" class="form-control">
                            </label>
                        <?php endif; ?>

                        <?php if (in_array($listOfTable, ['pm_subtask'])): ?>
                            <label>
                                <input type="text" name="time_estimated" placeholder="<?= ___('time_estimated') ?>" class="form-control">
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="mt-2">
                        <?php if (in_array($listOfTable, ['pm_organization', 'pm_department', 'pm_project', 'pm_task', 'pm_subtask'])): ?>
                            <label>

                                <select name="manager_id" class="form-control">
                                    <option value=""><?= ___('manager_id') ?></option>
                                    <?php foreach ($userList as $user): ?>
                                        <option value="<?= $user->id ?>">
                                            <?= implode(' ', [
                                                $user->firstname,
                                                $user->lastname,
                                                $user->mail,
                                            ]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>


                        <?php if (in_array($listOfTable, ['pm_project', 'pm_task', 'pm_subtask'])): ?>
                            <label>

                                <select name="assignee_id" class="form-control">
                                    <option value=""><?= ___('assignee_id') ?></option>
                                    <?php foreach ($userList as $user): ?>
                                        <option value="<?= $user->id ?>">
                                            <?= implode(' ', [
                                                $user->firstname,
                                                $user->lastname,
                                                $user->mail,
                                            ]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>
                    </div>
                    <!--#####-->
                </form>
            <?php endif; ?>
            <!--endregion FORM NEW-->
            <!--########################################################################################################################-->

            <!--########################################################################################################################-->
            <!--region LIST -->
            <?php if (!empty($list)): ?>
                <div>

                    <!--########################################################################################################################-->
                    <!--region FORM ORDER -->
                    <?php if (in_array($listOfTable, ['pm_task', 'pm_subtask'])): ?>
                        <form action="" id="order_in_view" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="form_id" value="order_in_view">
                            <input type="hidden" name="do" value="order_in_view">
                            <input type="hidden" name="model" value="<?= $listOfTable ?>">
                            <button type="submit" form="order_in_view" class="btn btn-sm btn-primary"><?= ___("Save New Order") ?></button>
                        </form>
                    <?php endif; ?>
                    <!--endregion FORM ORDER -->
                    <!--########################################################################################################################-->

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

                                    <form action="" method="post" class="d-inline">
                                        <input type="hidden" name="form_id" value="delete_model">
                                        <input type="hidden" name="do" value="delete_model">
                                        <input type="hidden" name="model" value="<?= $listOfTable ?>">
                                        <input type="hidden" name="id" value="<?= $item->id ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('<?= ___("Are you sure?") ?>');"
                                        ><?= ___("Delete") ?></button>
                                    </form>
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

