<?php

namespace alina\mvc\Controller;

use alina\AppExceptionValidation;
use alina\Message;
use alina\mvc\Model\_BaseAlinaModel;
use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\modelNamesResolver;
use alina\mvc\Model\pm_department;
use alina\mvc\Model\pm_organization;
use alina\mvc\Model\pm_project;
use alina\mvc\Model\pm_subtask;
use alina\mvc\Model\pm_task;
use alina\mvc\Model\pm_work;
use alina\mvc\Model\pm_work_done;
use alina\mvc\Model\user;
use alina\mvc\View\html;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\DateTime;
use alina\Utils\Request;

class Pm
{
    const URL_FILL_REPORT = '/pm/fill';
    const URL_EDIT        = '/pm/edit';
    const URL_REPORT      = '/pm/report';

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
        $vd['listWorkDone']  = [];
        $vd['breadcrumbs']   = [];
        ##################################################
        $href                = static::URL_FILL_REPORT;
        $vd['breadcrumbs'][] = [
            'href'  => $href,
            'txt'   => ___('Home'),
            'table' => '',
        ];
        $mOrganization       = new pm_organization();
        $mDepartment         = new pm_department();
        $mProject            = new pm_project();
        $mTask               = new pm_task();
        $mSubTask            = new pm_subtask();
        $mWork               = new pm_work();
        $mWorkDone           = new pm_work_done();
        ##################################################
        if (empty($organization_id)) {
            $vd['list']        = $mOrganization->getAllWithReferences()->toArray();
            $vd['listOfTable'] = $mOrganization->table;
        } else {
            $mOrganization->getOneWithReferencesById($organization_id);
            $href                = "$href/$mOrganization->id";
            $vd['breadcrumbs'][] = [
                'txt'   => $mOrganization->attributes->name_human,
                'href'  => $href,
                'table' => $mOrganization->table,
            ];
            if (empty($department_id)) {
                $vd['list']        = $mDepartment->getAllWithReferences([['pm_organization_id', '=', $organization_id]])->toArray();
                $vd['listOfTable'] = $mDepartment->table;
            } else {
                $mDepartment->getOneWithReferencesById($department_id);
                $href                = "$href/$mDepartment->id";
                $vd['breadcrumbs'][] = [
                    'txt'   => $mDepartment->attributes->name_human,
                    'href'  => $href,
                    'table' => $mDepartment->table,
                ];

                if (empty($project_id)) {
                    $vd['list']        = $mProject->getAllWithReferences([['pm_department_id', '=', $department_id]])->toArray();
                    $vd['listOfTable'] = $mProject->table;
                } else {
                    $mProject->getOneWithReferencesById($project_id);
                    $href                = "$href/$mProject->id";
                    $vd['breadcrumbs'][] = [
                        'txt'   => $mProject->attributes->name_human,
                        'href'  => $href,
                        'table' => $mProject->table,
                    ];

                    if (empty($task_id)) {
                        $vd['list']        = $mTask->getAllWithReferences([['pm_project_id', '=', $project_id]])->toArray();
                        $vd['listOfTable'] = $mTask->table;
                    } else {
                        $mTask->getOneWithReferencesById($task_id);
                        $href                = "$href/$mTask->id";
                        $vd['breadcrumbs'][] = [
                            'txt'   => $mTask->attributes->name_human,
                            'href'  => $href,
                            'table' => $mTask->table,
                        ];

                        if (empty($subtask_id)) {
                            $vd['list']        = $mSubTask->getAllWithReferences([['pm_task_id', '=', $task_id]])->toArray();
                            $vd['listOfTable'] = $mSubTask->table;
                        } else {
                            $mSubTask->getOneWithReferencesById($subtask_id);
                            $href                = "$href/$mSubTask->id";
                            $vd['breadcrumbs'][] = [
                                'txt'   => $mSubTask->attributes->name_human,
                                'href'  => $href,
                                'table' => $mSubTask->table,
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
                                    'txt'   => $mWork->attributes->name_human,
                                    'href'  => $href,
                                    'table' => $mWork->table,
                                ];

                                ##################################################
                                #region POST
                                if (Request::obj()->isPostPutDelete()) {
                                    switch (Request::obj()->POST->do) {
                                        case 'insert_pm_work_done':
                                            $amount     = Request::obj()->POST->amount;
                                            $pm_work_id = Request::obj()->POST->pm_work_id;
                                            $for_date   = ALINA_TIME;
                                            if (Request::obj()->POST->for_date) {
                                                $for_date = DateTime::dateToUnixTime(Request::obj()->POST->for_date);
                                            }
                                            $mWorkDone->insert([
                                                'amount'     => $amount,
                                                'pm_work_id' => $pm_work_id,
                                                'for_date'   => $for_date,
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
                                        [
                                            ["$mWorkDone->alias.for_date", 'DESC'],
                                            ["$mWorkDone->alias.modified_at", 'DESC'],
                                        ]
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
        $href                = static::URL_EDIT;
        $vd['breadcrumbs'][] = [
            'href'  => $href,
            'txt'   => ___('Home'),
            'table' => '',
        ];

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
                    $m = modelNamesResolver::getModelObject($p->model);
                    $m->smartDeleteById($p->id);
                    break;
                case 'insert_pm_work_done':
                    $amount     = Request::obj()->POST->amount;
                    $pm_work_id = Request::obj()->POST->pm_work_id;
                    $for_date   = ALINA_TIME;
                    if (Request::obj()->POST->for_date) {
                        $for_date = DateTime::dateToUnixTime(Request::obj()->POST->for_date);
                    }
                    $assignee_id = CurrentUser::id();
                    if (Request::obj()->POST->assignee_id) {
                        $assignee_id = Request::obj()->POST->assignee_id;
                    }
                    $mWorkDone->insert([
                        'amount'      => $amount,
                        'pm_work_id'  => $pm_work_id,
                        'for_date'    => $for_date,
                        'assignee_id' => $assignee_id,
                    ]);
                    break;
                case 'delete_pm_work_done':
                    $pm_work_done_id = Request::obj()->POST->pm_work_done_id;
                    (new pm_work_done())->smartDeleteById($pm_work_done_id);
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
                'txt'   => $mOrganization->attributes->name_human,
                'href'  => $href,
                'table' => $mOrganization->table,
            ];
            if (empty($department_id)) {
                $vd['list']        = $mDepartment->getAllWithReferences([['pm_organization_id', '=', $organization_id]])->toArray();
                $vd['listOfTable'] = $mDepartment->table;
            } else {
                $mDepartment->getOneWithReferencesById($department_id);
                $href                = "$href/$mDepartment->id";
                $vd['breadcrumbs'][] = [
                    'txt'   => $mDepartment->attributes->name_human,
                    'href'  => $href,
                    'table' => $mDepartment->table,
                ];

                if (empty($project_id)) {
                    $vd['list']        = $mProject->getAllWithReferences([['pm_department_id', '=', $department_id]])->toArray();
                    $vd['listOfTable'] = $mProject->table;
                } else {
                    $mProject->getOneWithReferencesById($project_id);
                    $href                = "$href/$mProject->id";
                    $vd['breadcrumbs'][] = [
                        'txt'   => $mProject->attributes->name_human,
                        'href'  => $href,
                        'table' => $mProject->table,
                    ];

                    if (empty($task_id)) {
                        $vd['list']        = $mTask->getAllWithReferences([['pm_project_id', '=', $project_id]])->toArray();
                        $vd['listOfTable'] = $mTask->table;
                    } else {
                        $mTask->getOneWithReferencesById($task_id);
                        $href                = "$href/$mTask->id";
                        $vd['breadcrumbs'][] = [
                            'txt'   => $mTask->attributes->name_human,
                            'href'  => $href,
                            'table' => $mTask->table,
                        ];

                        if (empty($subtask_id)) {
                            $vd['list']        = $mSubTask->getAllWithReferences([['pm_task_id', '=', $task_id]])->toArray();
                            $vd['listOfTable'] = $mSubTask->table;
                        } else {
                            $mSubTask->getOneWithReferencesById($subtask_id);
                            $href                = "$href/$mSubTask->id";
                            $vd['breadcrumbs'][] = [
                                'txt'   => $mSubTask->attributes->name_human,
                                'href'  => $href,
                                'table' => $mSubTask->table,
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
                                    'txt'   => $mWork->attributes->name_human,
                                    'href'  => $href,
                                    'table' => $mWork->table,
                                ];

                                $vd['listWorkDone'] = $mWorkDone
                                    ->getAllWithReferences([
                                        ["$mWorkDone->alias.pm_work_id", '=', $work_id],
                                        ["$mWorkDone->alias.flag_archived", '=', 0],
                                    ],
                                        [
                                            ["$mWorkDone->alias.modified_at", 'DESC'],
                                            ["$mWorkDone->alias.$mWorkDone->pkName", 'DESC'],
                                        ]
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

    public function actionReport()
    {
        $res = [];
        $vd  = [];
        $GET = Request::obj()->GET;
        ##################################################
        $idxControl    = [];
        $byUsers       = [];
        $byUsersTotals = [];
        $ud            = [];
        $od            = [];
        $odTotals      = [];
        $dd            = [];
        $ddTotals      = [];
        $pd            = [];
        $pdTotals      = [];
        ##################################################
        if (
            !empty(Request::obj()->GET->date_start)
            &&
            !empty(Request::obj()->GET->date_end)
        ) {
            ##################################################
            $date_start = $GET->date_start;
            $date_end   = $GET->date_end;

            $dateToUtDayStart = DateTime::dateToUtDayStart($date_start);
            $dateToUtDayEnd   = DateTime::dateToUtDayEnd($date_end);

            $s = DateTime::toHumanDateTime($dateToUtDayStart);
            $e = DateTime::toHumanDateTime($dateToUtDayEnd);
            ##################################################
            $vd['date_start']       = $date_start;
            $vd['date_end']         = $date_end;
            $vd['dateToUtDayStart'] = $dateToUtDayStart;
            $vd['dateToUtDayEnd']   = $dateToUtDayEnd;
            $vd['s']                = $s;
            $vd['e']                = $e;
            ##################################################
            $sql       = (new html)->piece('/Pm/sql/report_001.php', $vd);
            $vd['sql'] = $sql;
            ##################################################
            $m         = new _BaseAlinaModel();
            $res       = $m->x($sql)->fetchAll(\PDO::FETCH_OBJ);
            $vd['res'] = $res;
            ##################################################


            foreach ($res as $idx => $r) {
                if (in_array($r->wd_id, $idxControl)) {
                    Message::setDanger(___('WD_ID`s are repeated!!!') . ' ' . $r->wd_id);
                }
                $idxControl[] = $r->wd_id;
                ##############################
                $assaId  = $r->wd_assignee_id;
                $oid     = $r->o_id;
                $did     = $r->d_id;
                $pid     = $r->p_id;
                $tid     = $r->t_id;
                $stid    = $r->st_id;
                $wid     = $r->w_id;
                $wdid    = $r->wd_id;
                $afn     = implode(' ', [$r->assa_firstname, $r->assa_lastname, "[$assaId]"]);
                $onh     = $r->o_nh;
                $dnh     = $r->d_nh;
                $pnh     = $r->p_nh;
                $tnh     = $r->t_nh;
                $stnh    = $r->st_nh;
                $wnh     = $r->w_nh;
                $forDate = DateTime::toHumanDate($r->wd_for_date);
                $amnt    = $r->wd_amount;
                $pf      = $r->wd_price_final;
                $ts      = $r->wd_time_spent;
                $fa      = $r->wd_flag_archived;

                ####################################################################################################

                if (empty($byUsers[$assaId])) $byUsers[$assaId] = [];
                if (empty($byUsers[$assaId]['full_name'])) $byUsers[$assaId]['full_name'] = $afn;
                if (empty($byUsers[$assaId]['price_total'])) $byUsers[$assaId]['price_total'] = 0;
                if (empty($byUsers[$assaId]['time_total'])) $byUsers[$assaId]['time_total'] = 0;

                $byUsers[$assaId]['price_total'] += $pf;
                $byUsers[$assaId]['time_total']  += $ts;
                #####
                if (empty($byUsersTotals['xxx'])) $byUsersTotals['xxx'] = [];
                if (empty($byUsersTotals['xxx']['full_name'])) $byUsersTotals['xxx']['full_name'] = ___('sum');
                if (empty($byUsersTotals['xxx']['price_total'])) $byUsersTotals['xxx']['price_total'] = 0;
                if (empty($byUsersTotals['xxx']['time_total'])) $byUsersTotals['xxx']['time_total'] = 0;

                $byUsersTotals['xxx']['price_total'] += $pf;
                $byUsersTotals['xxx']['time_total']  += $ts;

                ####################################################################################################

                if (empty($od[$oid])) $od[$oid] = [];
                if (empty($od[$oid]['full_name'])) $od[$oid]['full_name'] = $onh;
                if (empty($od[$oid]['price_total'])) $od[$oid]['price_total'] = 0;
                if (empty($od[$oid]['time_total'])) $od[$oid]['time_total'] = 0;

                $od[$oid]['price_total'] += $pf;
                $od[$oid]['time_total']  += $ts;
                #####
                if (empty($odTotals['xxx'])) $odTotals['xxx'] = [];
                if (empty($odTotals['xxx']['full_name'])) $odTotals['xxx']['full_name'] = ___('sum');
                if (empty($odTotals['xxx']['price_total'])) $odTotals['xxx']['price_total'] = 0;
                if (empty($odTotals['xxx']['time_total'])) $odTotals['xxx']['time_total'] = 0;

                $odTotals['xxx']['price_total'] += $pf;
                $odTotals['xxx']['time_total']  += $ts;

                ####################################################################################################

                if (empty($dd[$did])) $dd[$did] = [];
                if (empty($dd[$did]['full_name'])) $dd[$did]['full_name'] = implode(' ', [$dnh, "[$onh]"]);
                if (empty($dd[$did]['price_total'])) $dd[$did]['price_total'] = 0;
                if (empty($dd[$did]['time_total'])) $dd[$did]['time_total'] = 0;

                $dd[$did]['price_total'] += $pf;
                $dd[$did]['time_total']  += $ts;
                #####
                if (empty($ddTotals['xxx'])) $ddTotals['xxx'] = [];
                if (empty($ddTotals['xxx']['full_name'])) $ddTotals['xxx']['full_name'] = ___('sum');
                if (empty($ddTotals['xxx']['price_total'])) $ddTotals['xxx']['price_total'] = 0;
                if (empty($ddTotals['xxx']['time_total'])) $ddTotals['xxx']['time_total'] = 0;

                $ddTotals['xxx']['price_total'] += $pf;
                $ddTotals['xxx']['time_total']  += $ts;

                ####################################################################################################


                if (empty($pd[$pid])) $pd[$pid] = [];
                if (empty($pd[$pid]['full_name'])) $pd[$pid]['full_name'] = implode(' ', [$pnh, "[$onh, $dnh]"]);
                if (empty($pd[$pid]['price_total'])) $pd[$pid]['price_total'] = 0;
                if (empty($pd[$pid]['time_total'])) $pd[$pid]['time_total'] = 0;

                $pd[$pid]['price_total'] += $pf;
                $pd[$pid]['time_total']  += $ts;
                #####
                if (empty($pdTotals['xxx'])) $pdTotals['xxx'] = [];
                if (empty($pdTotals['xxx']['full_name'])) $pdTotals['xxx']['full_name'] = ___('sum');
                if (empty($pdTotals['xxx']['price_total'])) $pdTotals['xxx']['price_total'] = 0;
                if (empty($pdTotals['xxx']['time_total'])) $pdTotals['xxx']['time_total'] = 0;

                $pdTotals['xxx']['price_total'] += $pf;
                $pdTotals['xxx']['time_total']  += $ts;

                ####################################################################################################


                $zzz[$assaId][$afn][$oid][$onh][$did][$dnh][$pid][$pnh][$tid][$tnh][$stid][$stnh][$wid][$wnh][$wdid] = [
                    'for_date'    => $forDate,
                    'amount'      => $amnt,
                    'price_final' => $pf,
                    'time_spent'  => $ts,
                ];

                ####################################################################################################

                if (empty($ud[$assaId]['sum']['amount'])) $ud[$assaId]['sum']['amount'] = 0;
                if (empty($ud[$assaId]['sum']['price_final'])) $ud[$assaId]['sum']['price_final'] = 0;
                if (empty($ud[$assaId]['sum']['time_spent'])) $ud[$assaId]['sum']['time_spent'] = 0;

                $ud[$assaId][$wdid] = [
                    'full_name'        => $afn,
                    'organization'     => $onh,
                    'department'       => $dnh,
                    'project'          => $pnh,
                    'task'             => $tnh,
                    'subtask'          => $stnh,
                    'for_date'         => $forDate,
                    'amount'           => $amnt,
                    'price_final'      => $pf,
                    'time_spent'       => $ts,
                    'wd_flag_archived' => $fa,
                ];

                $sumAmnt = $ud[$assaId]['sum']['amount'] + $ud[$assaId][$wdid]['amount'];
                $sumPf   = $ud[$assaId]['sum']['price_final'] + $ud[$assaId][$wdid]['price_final'];
                $sumTs   = $ud[$assaId]['sum']['time_spent'] + $ud[$assaId][$wdid]['time_spent'];

                $ud[$assaId]['sum'] = [
                    'full_name'        => '',
                    'organization'     => '',
                    'department'       => '',
                    'project'          => '',
                    'task'             => '',
                    'subtask'          => '',
                    'for_date'         => '',
                    'amount'           => $sumAmnt,
                    'price_final'      => $sumPf,
                    'time_spent'       => $sumTs,
                    'wd_flag_archived' => '',
                ];


                ####################################################################################################


                ####################################################################################################
            }
            $byUsers[] = $byUsersTotals['xxx'];
            #
            $od[] = $odTotals['xxx'];
            #
            $dd[] = $ddTotals['xxx'];
            #
            $pd[] = $pdTotals['xxx'];
            ##################################################
        }
        $vd['byUsers'] = $byUsers;
        $vd['od']      = $od;
        $vd['dd']      = $dd;
        $vd['pd']      = $pd;
        $vd['ud']      = $ud;
        $vd['res']     = $res;
        $vd['zzz']     = Data::toObject([]);
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
        return $this;
    }

    ###

}
