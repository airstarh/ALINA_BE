<?php

namespace alina\mvc\Controller;

use alina\mvc\Model\pm_department;
use alina\mvc\Model\pm_organization;
use alina\mvc\Model\pm_project;
use alina\mvc\Model\pm_subtask;
use alina\mvc\Model\pm_task;
use alina\mvc\Model\pm_work;
use alina\mvc\Model\pm_work_done;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Request;

class Pm
{
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

    public function actionFillWorkUnitDone(
        $organization_id = null,
        $department_id = null,
        $project_id = null,
        $task_id = null,
        $subtask_id = null,
        $work_id = null
    )
    {
        $vd                  = [];
        $vd['func_get_args'] = func_get_args();
        $vd['list']          = [];
        $vd['breadcrumbs']   = [];
        $vd['listWorkDone']  = [];
        ##################################################
        $href          = '/pm/FillWorkUnitDone';
        $mOrganization = new pm_organization();
        $mDepartment   = new pm_department();
        $mProject      = new pm_project();
        $mTask         = new pm_task();
        $mSubTask      = new pm_subtask();
        $mWork         = new pm_work();
        $mWorkDone     = new pm_work_done();
        ##################################################
        if (empty($organization_id)) {
            $vd['list'] = $mOrganization->getAllWithReferences()->toArray();
        } else {
            $mOrganization->getOneWithReferencesById($organization_id);
            $href                = "$href/$mOrganization->id";
            $vd['breadcrumbs'][] = [
                'txt'  => $mOrganization->attributes->name_human,
                'href' => $href,
            ];
            if (empty($department_id)) {
                $vd['list'] = $mDepartment->getAllWithReferences([['pm_organization_id', '=', $organization_id]])->toArray();
            } else {
                $mDepartment->getOneWithReferencesById($department_id);
                $href                = "$href/$mDepartment->id";
                $vd['breadcrumbs'][] = [
                    'txt'  => $mDepartment->attributes->name_human,
                    'href' => $href,
                ];

                if (empty($project_id)) {
                    $vd['list'] = $mProject->getAllWithReferences([['pm_department_id', '=', $department_id]])->toArray();
                } else {
                    $mProject->getOneWithReferencesById($project_id);
                    $href                = "$href/$mProject->id";
                    $vd['breadcrumbs'][] = [
                        'txt'  => $mProject->attributes->name_human,
                        'href' => $href,
                    ];

                    if (empty($task_id)) {
                        $vd['list'] = $mTask->getAllWithReferences([['pm_project_id', '=', $project_id]])->toArray();
                    } else {
                        $mTask->getOneWithReferencesById($task_id);
                        $href                = "$href/$mTask->id";
                        $vd['breadcrumbs'][] = [
                            'txt'  => $mTask->attributes->name_human,
                            'href' => $href,
                        ];

                        if (empty($subtask_id)) {
                            $vd['list'] = $mSubTask->getAllWithReferences([['pm_task_id', '=', $task_id]])->toArray();
                        } else {
                            $mSubTask->getOneWithReferencesById($subtask_id);
                            $href                = "$href/$mSubTask->id";
                            $vd['breadcrumbs'][] = [
                                'txt'  => $mSubTask->attributes->name_human,
                                'href' => $href,
                            ];

                            if (empty($work_id)) {
                                $vd['list'] = $mWork->getAllWithReferences([
                                    ['flag_archived', '=', 0],
                                    ["$mWork->alias.pm_organization_id", '=', $organization_id],
                                    ["$mWork->alias.pm_department_id", '=', $department_id],
                                    ["$mWork->alias.pm_project_id", '=', $project_id],
                                    ["$mWork->alias.pm_task_id", '=', $task_id],
                                    ["$mWork->alias.pm_subtask_id", '=', $subtask_id],
                                ])->toArray();
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
                                );

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
        $vd['url']           = $this->url(...func_get_args());
        ##################################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
        return $this;
    }

    public function url(...$args)
    {
        $url = '/pm/FillWorkUnitDone';
        $res = $args;
        array_unshift($res, $url);
        $res = array_filter($res);
        return implode('/', $res);
    }

    ###

}
