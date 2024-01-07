<?php

namespace alina\mvc\Model;

use alina\Message;

class pm_work_story extends _BaseAlinaModel
{
    public $table        = 'pm_work_story';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'                 => [],
            'name_human'         => [],
            'wd_assignee_id'     => [],
            'pm_organization_id' => [],
            'pm_department_id'   => [],
            'pm_project_id'      => [],
            'pm_task_id'         => [],
            'pm_subtask_id'      => [],
            'pm_work_id'         => [],
            'pm_work_done_id'    => [],
            'created_at'         => [],
            'created_by'         => [],
            'modified_at'        => [],
            'modified_by'        => [],
            'd_price_min'        => [],
            'p_price_multiplier' => [],
            'st_time_estimated'  => [],
            'w_price_this_work'  => [],
            'wd_for_date'        => [],
            'wd_amount'          => [],
            'wd_price_final'     => [],
            'wd_time_spent'      => [],
        ];
    }

    #####
    public function uniqueKeys()
    {
        return [
            ['wd_assignee_id', 'pm_organization_id', 'pm_department_id', 'pm_project_id', 'pm_task_id', 'pm_subtask_id', 'pm_work_id', 'pm_work_done_id'],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field ######
            'pm_organization_id' => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_organization',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'pm_organization AS pm_organization', 'pm_organization.id', '=', "{$this->alias}.pm_organization_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_organization.name_human AS pm_organization.name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_department_id'   => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_department',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'pm_department AS pm_department', 'pm_department.id', '=', "{$this->alias}.pm_department_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_department.name_human AS pm_department.name_human',
                            'pm_department.price_min AS pm_department.price_min',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_project_id'      => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_project',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'pm_project AS pm_project', 'pm_project.id', '=', "{$this->alias}.pm_project_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_project.name_human AS pm_project.name_human',
                            'pm_project.price_multiplier AS pm_project.price_multiplier',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_task_id'         => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_task',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'pm_task AS pm_task', 'pm_task.id', '=', "{$this->alias}.pm_task_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_task.name_human AS pm_task.name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_subtask_id'      => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_subtask',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'pm_subtask AS pm_subtask', 'pm_subtask.id', '=', "{$this->alias}.pm_subtask_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_subtask.name_human AS pm_subtask.name_human',
                            'pm_subtask.time_estimated AS pm_subtask.time_estimated',
                            'pm_subtask.price AS pm_subtask.price',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }

    public function boundModelsByWorkDoneId($idWorkDone)
    {
        static $models = [];
        if (
            (isset($models['mW']))
            &&
            (isset($models['mWd']))
            &&
            (isset($models['mAssa']))
        ) {
            return $models;
        }
        $mWd   = new pm_work_done();
        $mW    = new pm_work();
        $mAssa = new user();
        $mWd->getById($idWorkDone);
        $mW->getById($mWd->attributes->pm_work_id);
        $mW->getParents();
        $mAssa->getById($mWd->attributes->assignee_id);
        $models = [
            'mW'    => $mW,
            'mWd'   => $mWd,
            'mAssa' => $mAssa,
        ];
        return $models;
    }


    public function doArchiveWorkDone($idWorkDone)
    {
        $models = $this->boundModelsByWorkDoneId($idWorkDone);
        $mWd    = $models['mWd'];
        $mW     = $models['mW'];
        $mAssa  = $models['mAssa'];

        $dataWorkStory = [
            'pm_work_done_id'    => $mWd->attributes->id,
            'wd_assignee_id'     => $mWd->attributes->assignee_id,
            'pm_organization_id' => $mW->attributes->pm_organization->id,
            'pm_department_id'   => $mW->attributes->pm_department->id,
            'pm_project_id'      => $mW->attributes->pm_project->id,
            'pm_task_id'         => $mW->attributes->pm_task->id,
            'pm_subtask_id'      => $mW->attributes->pm_subtask->id,
            'pm_work_id'         => $mW->attributes->id,
        ];

        $this->upsertByUniqueFields($dataWorkStory, [
            [
                'wd_assignee_id',
                'pm_organization_id',
                'pm_department_id',
                'pm_project_id',
                'pm_task_id',
                'pm_subtask_id',
                'pm_work_id',
                'pm_work_done_id',
            ],
        ]);
        return $this;
    }


    public function hookRightBeforeSave(&$dataArray)
    {
        $models = $this->boundModelsByWorkDoneId($dataArray['pm_work_done_id']);
        $mWd    = $models['mWd'];
        $mW     = $models['mW'];
        $mAssa  = $models['mAssa'];
        ###
        $wd_for_date        = $mWd->attributes->for_date;
        $wd_amount          = $mWd->attributes->amount;
        $wd_price_final     = $mWd->attributes->price_final;
        $wd_time_spent      = $mWd->attributes->time_spent;
        $d_price_min        = $mW->attributes->pm_department->price_min;
        $p_price_multiplier = $mW->attributes->pm_project->price_multiplier;
        $st_time_estimated  = $mW->attributes->pm_subtask->time_estimated;
        $w_price_this_work  = $mW->attributes->price_this_work;
        ###
        $d                       = [
            'wd_for_date'        => $wd_for_date,
            'd_price_min'        => $d_price_min,
            'p_price_multiplier' => $p_price_multiplier,
            'wd_amount'          => $wd_amount,
            'wd_price_final'     => $wd_price_final,
            'wd_time_spent'      => $wd_time_spent,
            'w_price_this_work'  => $w_price_this_work,
            'st_time_estimated'  => $st_time_estimated,
        ];
        $dataArray               = array_merge($dataArray, $d);
        $name_human              = $this->calcNameHuman($dataArray);
        $dataArray['name_human'] = $name_human;
        return $this;
    }

    public function calcNameHuman($dataArray)
    {
        $models = $this->boundModelsByWorkDoneId($dataArray['pm_work_done_id']);
        $mWd    = $models['mWd'];
        $mW     = $models['mW'];
        $mAssa  = $models['mAssa'];
        ###
        $d
            = array_merge(
            [
                'assa_id'        => $mAssa->attributes->id,
                'assa_firstname' => $mAssa->attributes->firstname,
                'assa_lastname'  => $mAssa->attributes->lastname,
                'assa_mail'      => $mAssa->attributes->mail,
            ],
            $dataArray);
        return json_encode($d);
    }

    public function hookRightAfterSave($data)
    {
        Message::setInfo(implode(' ', [
            'Archived Work Done:',
            $data->id,
        ]));
    }
    #####
}
