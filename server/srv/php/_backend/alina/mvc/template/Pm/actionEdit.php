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
            </h1>
            <div class="clear">&nbsp;</div>

            <?= (new html)->piece('Pm/_pmBreadCrumbs.php', $breadcrumbs) ?>

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
                            <input type="text" name="name_human" placeholder="<?= ___('name_human') ?>" class="form-control required" required>
                        </label>

                        <?php if (in_array($listOfTable, ['pm_department'])): ?>
                            <label>
                                <input type="text" name="price_min" placeholder="<?= ___('price_min') ?>" class="form-control" required>
                            </label>
                        <?php endif; ?>

                        <?php if (in_array($listOfTable, ['pm_project'])): ?>
                            <label>
                                <input type="text" name="price_multiplier" placeholder="<?= ___('price_multiplier') ?>" class="form-control" required>
                            </label>
                        <?php endif; ?>

                        <?php if (in_array($listOfTable, ['pm_subtask'])): ?>
                            <label>
                                <input type="text" name="time_estimated" placeholder="<?= ___('time_estimated') ?>" class="form-control" required>
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
                                    >
                                        <?php if ($listOfTable === 'pm_work'): ?>
                                            <?= (new html())->piece('_system/html/_form/table002.php', json_decode($item->name_human)) ?>
                                        <?php else: ?>
                                            <?= $item->name_human ?>
                                        <?php endif; ?>
                                    </a>

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


            <div>
                <!--########################################################################################################################-->
                <!--region FORM FILL WORK DONE -->
                <?php if ($mWork->id): ?>
                    <div class="mt-5 mb-5">
                        <form action="" method="post">
                            <input type="hidden" name="form_id" value="actionFillWorkUnitDone">
                            <input type="hidden" name="do" value="insert_pm_work_done">
                            <input type="hidden" name="pm_work_id" value="<?= $mWork->id ?>">

                            <div class="text-center">

                                <div class="m-3">
                                    <label>
                                        <select name="assignee_id" class="form-control">
                                            <option value=""><?= ___('assignee_id') ?></option>
                                            <?php foreach ($userList as $user): ?>
                                                <option value="<?= $user->id ?>">
                                                    <?= implode(' ', [
                                                        $user->lastname,
                                                        $user->firstname,
                                                        $user->mail,
                                                    ]); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>

                                <div>
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
                                </div>

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
                        <div>
                            <table class="bg-black alina-data-table">
                                <thead>
                                <tr>
                                    <th><?= ___("for_date") ?></th>
                                    <th><?= ___("full_name") ?></th>
                                    <th><?= ___("amount") ?></th>
                                    <th><?= ___("price_final") ?></th>
                                    <th><?= ___("time_spent") ?></th>
                                    <th></th>
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
                <!--endregion FORM FILL WORK DONE -->
                <!--########################################################################################################################-->
            </div>


            <!--endregion PAGE-->
            <!--##################################################-->
        </div>
    </div>
</div>

