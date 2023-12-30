<?php

namespace alina\mvc\Controller;

use alina\Message;
use alina\mvc\Model\modelNamesResolver;
use alina\mvc\Model\pm_department;
use alina\mvc\Model\pm_organization;
use alina\mvc\Model\pm_project;
use alina\mvc\Model\pm_subtask;
use alina\mvc\Model\pm_task;
use alina\mvc\Model\pm_work;
use alina\mvc\Model\pm_work_done;
use alina\mvc\Model\user;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Request;

class Pm
{
    const URL_FILL_REPORT = '/pm/fill';
    const URL_EDIT        = '/pm/edit';

    public function __construct()
    {

    }

    /**
     * @route /Generic/index
     * @route /Generic/index/test/path/parameters
     */
    public function actionIndex(...$arg)
    {
        $vd = [
            'args' => $arg,
        ];

        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);

        return $this;
    }

    public function actionFill(
        $organization_id = null,
        $department_id = null,
        $project_id = null,
        $task_id = null,
        $subtask_id = null,
        $work_id = null
    )
    {
        $vd = [];
        ##################################################
        $vd['pm_organization_id'] = $organization_id;
        $vd['pm_department_id']   = $department_id;
        $vd['pm_project_id']      = $project_id;
        $vd['pm_task_id']         = $task_id;
        $vd['pm_subtask_id']      = $subtask_id;
        ##################################################
        $vd['func_get_args'] = func_get_args();
        $vd['list']          = [];
        $vd['listOfTable']   = null;
        $vd['breadcrumbs']   = [];
        $vd['listWorkDone']  = [];
        ##################################################
        $href          = static::URL_FILL_REPORT;
        $mOrganization = new pm_organization();
        $mDepartment   = new pm_department();
        $mProject      = new pm_project();
        $mTask         = new pm_task();
        $mSubTask      = new pm_subtask();
        $mWork         = new pm_work();
        $mWorkDone     = new pm_work_done();
        ##################################################
        if (empty($organization_id)) {
            $vd['list']        = $mOrganization->getAllWithReferences()->toArray();
            $vd['listOfTable'] = $mOrganization->table;
        } else {
            $mOrganization->getOneWithReferencesById($organization_id);
            $href                = "$href/$mOrganization->id";
            $vd['breadcrumbs'][] = [
                'txt'  => $mOrganization->attributes->name_human,
                'href' => $href,
            ];
            if (empty($department_id)) {
                $vd['list']        = $mDepartment->getAllWithReferences([['pm_organization_id', '=', $organization_id]])->toArray();
                $vd['listOfTable'] = $mDepartment->table;
            } else {
                $mDepartment->getOneWithReferencesById($department_id);
                $href                = "$href/$mDepartment->id";
                $vd['breadcrumbs'][] = [
                    'txt'  => $mDepartment->attributes->name_human,
                    'href' => $href,
                ];

                if (empty($project_id)) {
                    $vd['list']        = $mProject->getAllWithReferences([['pm_department_id', '=', $department_id]])->toArray();
                    $vd['listOfTable'] = $mProject->table;
                } else {
                    $mProject->getOneWithReferencesById($project_id);
                    $href                = "$href/$mProject->id";
                    $vd['breadcrumbs'][] = [
                        'txt'  => $mProject->attributes->name_human,
                        'href' => $href,
                    ];

                    if (empty($task_id)) {
                        $vd['list']        = $mTask->getAllWithReferences([['pm_project_id', '=', $project_id]])->toArray();
                        $vd['listOfTable'] = $mTask->table;
                    } else {
                        $mTask->getOneWithReferencesById($task_id);
                        $href                = "$href/$mTask->id";
                        $vd['breadcrumbs'][] = [
                            'txt'  => $mTask->attributes->name_human,
                            'href' => $href,
                        ];

                        if (empty($subtask_id)) {
                            $vd['list']        = $mSubTask->getAllWithReferences([['pm_task_id', '=', $task_id]])->toArray();
                            $vd['listOfTable'] = $mSubTask->table;
                        } else {
                            $mSubTask->getOneWithReferencesById($subtask_id);
                            $href                = "$href/$mSubTask->id";
                            $vd['breadcrumbs'][] = [
                                'txt'  => $mSubTask->attributes->name_human,
                                'href' => $href,
                            ];

                            if (empty($work_id)) {
                                $vd['list']        = $mWork->getAllWithReferences([
                                    ['flag_archived', '=', 0],
                                    ["$mWork->alias.pm_organization_id", '=', $organization_id],
                                    ["$mWork->alias.pm_department_id", '=', $department_id],
                                    ["$mWork->alias.pm_project_id", '=', $project_id],
                                    ["$mWork->alias.pm_task_id", '=', $task_id],
                                    ["$mWork->alias.pm_subtask_id", '=', $subtask_id],
                                ])->toArray();
                                $vd['listOfTable'] = $mWork->table;
                            } else {
                                $mWork->getOneWithReferencesById($work_id);
                                $href                = "$href/$mWork->id";
                                $vd['breadcrumbs'][] = [
                                    'txt'  => $mWork->attributes->name_human,
                                    'href' => $href,
                                ];

                                ##################################################
                                #region POST
                                if (Request::obj()->isPostPutDelete()) {
                                    switch (Request::obj()->POST->do) {
                                        case 'insert_pm_work_done':
                                            $amount     = Request::obj()->POST->amount;
                                            $pm_work_id = Request::obj()->POST->pm_work_id;
                                            $mWorkDone->insert([
                                                'amount'     => $amount,
                                                'pm_work_id' => $pm_work_id,
                                            ]);
                                            break;
                                        case 'delete_pm_work_done':
                                            $pm_work_done_id = Request::obj()->POST->pm_work_done_id;
                                            (new pm_work_done())->smartDeleteById($pm_work_done_id);
                                            break;
                                    }
                                }
                                #endregion POST
                                ##################################################

                                $vd['listWorkDone'] = $mWorkDone->getAllWithReferences([
                                    ["$mWorkDone->alias.pm_work_id", '=', $work_id],
                                ],
                                    [["$mWorkDone->alias.modified_at", 'DESC']]
                                )
                                                                ->toArray()
                                ;

                            }

                        }

                    }

                }
            }
        }
        ##################################################

        ##################################################
        $vd['mOrganization'] = $mOrganization->attributes;
        $vd['mDepartment']   = $mDepartment->attributes;
        $vd['mProject']      = $mProject->attributes;
        $vd['mTask']         = $mTask->attributes;
        $vd['mSubTask']      = $mSubTask->attributes;
        $vd['mWork']         = $mWork->attributes;
        $vd['mWorkDone']     = $mWorkDone->attributes;
        $vd['url']           = $this->url(...array_merge([static::URL_FILL_REPORT], func_get_args()));
        ##################################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
        return $this;
    }

    public function actionEdit(
        $organization_id = null,
        $department_id = null,
        $project_id = null,
        $task_id = null,
        $subtask_id = null,
        $work_id = null
    )
    {
        $vd = [];
        ##################################################
        $vd['pm_organization_id'] = $organization_id;
        $vd['pm_department_id']   = $department_id;
        $vd['pm_project_id']      = $project_id;
        $vd['pm_task_id']         = $task_id;
        $vd['pm_subtask_id']      = $subtask_id;
        ##################################################
        $vd['func_get_args'] = func_get_args();
        $vd['list']          = [];
        $vd['listOfTable']   = null;
        $vd['breadcrumbs']   = [];
        $vd['listWorkDone']  = [];
        ##################################################
        $href          = static::URL_EDIT;
        $mOrganization = new pm_organization();
        $mDepartment   = new pm_department();
        $mProject      = new pm_project();
        $mTask         = new pm_task();
        $mSubTask      = new pm_subtask();
        $mWork         = new pm_work();
        $mWorkDone     = new pm_work_done();
        ##################################################

        ##################################################
        #region POST 2
        if (Request::obj()->isPostPutDelete()) {
            $p = Request::obj()->POST;
            switch ($p->do) {
                case 'order_in_view':
                    foreach ($p->order_in_view as $id => $order) {

                        $m = modelNamesResolver::getModelObject($p->model);
                        $m->updateById([
                            'order_in_view' => $order,
                        ], $id);
                    }
                    break;
                case 'new_model':
                    $m = modelNamesResolver::getModelObject($p->model);
                    $m->insert($p);
                    break;
                case 'delete_model':
                    AlinaDebugJson($p);
                    $m = modelNamesResolver::getModelObject($p->model);
                    $m->smartDeleteById($p->id);
                    break;
            }
        }
        #endregion POST 2
        ##################################################

        if (empty($organization_id)) {
            $vd['list']        = $mOrganization->getAllWithReferences()->toArray();
            $vd['listOfTable'] = $mOrganization->table;
        } else {
            $mOrganization->getOneWithReferencesById($organization_id);
            $href                = "$href/$mOrganization->id";
            $vd['breadcrumbs'][] = [
                'txt'  => $mOrganization->attributes->name_human,
                'href' => $href,
            ];
            if (empty($department_id)) {
                $vd['list']        = $mDepartment->getAllWithReferences([['pm_organization_id', '=', $organization_id]])->toArray();
                $vd['listOfTable'] = $mDepartment->table;
            } else {
                $mDepartment->getOneWithReferencesById($department_id);
                $href                = "$href/$mDepartment->id";
                $vd['breadcrumbs'][] = [
                    'txt'  => $mDepartment->attributes->name_human,
                    'href' => $href,
                ];

                if (empty($project_id)) {
                    $vd['list']        = $mProject->getAllWithReferences([['pm_department_id', '=', $department_id]])->toArray();
                    $vd['listOfTable'] = $mProject->table;
                } else {
                    $mProject->getOneWithReferencesById($project_id);
                    $href                = "$href/$mProject->id";
                    $vd['breadcrumbs'][] = [
                        'txt'  => $mProject->attributes->name_human,
                        'href' => $href,
                    ];

                    if (empty($task_id)) {
                        $vd['list']        = $mTask->getAllWithReferences([['pm_project_id', '=', $project_id]])->toArray();
                        $vd['listOfTable'] = $mTask->table;
                    } else {
                        $mTask->getOneWithReferencesById($task_id);
                        $href                = "$href/$mTask->id";
                        $vd['breadcrumbs'][] = [
                            'txt'  => $mTask->attributes->name_human,
                            'href' => $href,
                        ];

                        if (empty($subtask_id)) {
                            $vd['list']        = $mSubTask->getAllWithReferences([['pm_task_id', '=', $task_id]])->toArray();
                            $vd['listOfTable'] = $mSubTask->table;
                        } else {
                            $mSubTask->getOneWithReferencesById($subtask_id);
                            $href                = "$href/$mSubTask->id";
                            $vd['breadcrumbs'][] = [
                                'txt'  => $mSubTask->attributes->name_human,
                                'href' => $href,
                            ];

                            if (empty($work_id)) {
                                $vd['list']        = $mWork->getAllWithReferences([
                                    ['flag_archived', '=', 0],
                                    ["$mWork->alias.pm_organization_id", '=', $organization_id],
                                    ["$mWork->alias.pm_department_id", '=', $department_id],
                                    ["$mWork->alias.pm_project_id", '=', $project_id],
                                    ["$mWork->alias.pm_task_id", '=', $task_id],
                                    ["$mWork->alias.pm_subtask_id", '=', $subtask_id],
                                ])->toArray();
                                $vd['listOfTable'] = $mWork->table;
                            } else {
                                $mWork->getOneWithReferencesById($work_id);
                                $href                = "$href/$mWork->id";
                                $vd['breadcrumbs'][] = [
                                    'txt'  => $mWork->attributes->name_human,
                                    'href' => $href,
                                ];

                                ##################################################
                                #region POST
                                if (Request::obj()->isPostPutDelete()) {

                                    switch (Request::obj()->POST->do) {
                                        case 'insert_pm_work_done':
                                            $amount     = Request::obj()->POST->amount;
                                            $pm_work_id = Request::obj()->POST->pm_work_id;
                                            $mWorkDone->insert([
                                                'amount'     => $amount,
                                                'pm_work_id' => $pm_work_id,
                                            ]);
                                            break;
                                        case 'delete_pm_work_done':
                                            $pm_work_done_id = Request::obj()->POST->pm_work_done_id;
                                            (new pm_work_done())->smartDeleteById($pm_work_done_id);
                                            break;
                                    }
                                }
                                #endregion POST
                                ##################################################

                                $vd['listWorkDone'] = $mWorkDone
                                    ->getAllWithReferences([
                                        ["$mWorkDone->alias.pm_work_id", '=', $work_id],
                                    ],
                                        [["$mWorkDone->alias.modified_at", 'DESC']]
                                    )
                                    ->toArray()
                                ;
                            }
                        }
                    }
                }
            }
        }
        ##################################################

        ##################################################
        $vd['mOrganization'] = $mOrganization->attributes;
        $vd['mDepartment']   = $mDepartment->attributes;
        $vd['mProject']      = $mProject->attributes;
        $vd['mTask']         = $mTask->attributes;
        $vd['mSubTask']      = $mSubTask->attributes;
        $vd['mWork']         = $mWork->attributes;
        $vd['mWorkDone']     = $mWorkDone->attributes;
        $vd['url']           = $this->url(...array_merge([static::URL_EDIT], func_get_args()));
        $vd['userList']      = (new user())->getAll()->toArray();
        ##################################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
        return $this;
    }


    public function url(...$args)
    {
        $res = array_filter($args);
        return implode('/', $res);
    }

    ###

}
