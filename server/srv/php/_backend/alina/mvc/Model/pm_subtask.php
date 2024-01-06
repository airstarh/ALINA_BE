<?php

namespace alina\mvc\Model;

use alina\Message;
use alina\Utils\Data;

class pm_subtask extends _BaseAlinaModel
{
    public $table        = 'pm_subtask';
    public $addAuditInfo = true;
    public $sortDefault  = [["order_in_view", 'ASC'], ["pm_task_id", 'ASC'], ["id", 'ASC']];

    public function fields()
    {
        return [
            'id'             => [],
            'name_human'     => [],
            'time_estimated' => [],
            'order_in_view'  => ['default' => 0],
            'pm_task_id'     => ['default' => 1,],
            'manager_id'     => [],
            'assignee_id'    => [],
            'price'          => [],
            'completed_at'   => [],
            'status'         => [],
            'created_at'     => [],
            'created_by'     => [],
            'modified_at'    => [],
            'modified_by'    => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field ######
            'manager_id'  => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'user',
                    'childPk'        => 'id',
                    'childHumanName' => ['firstname', 'lastname', 'mail'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'user AS manager', 'manager.id', '=', "{$this->alias}.manager_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'manager.firstname AS manager.firstname',
                            'manager.lastname AS manager.lastname',
                            'manager.mail AS manager.mail',
                            'manager.emblem AS manager.emblem',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'assignee_id' => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'user',
                    'childPk'        => 'id',
                    'childHumanName' => ['firstname', 'lastname', 'mail'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'user AS assignee', 'assignee.id', '=', "{$this->alias}.assignee_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'assignee.firstname AS assignee.firstname',
                            'assignee.lastname AS assignee.lastname',
                            'assignee.mail AS assignee.mail',
                            'assignee.emblem AS assignee.emblem',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_task_id'  => [
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
        ];
    }

    public function hookRightAfterSave($data)
    {
        if (!AlinaAccessIfAdmin() && !AlinaAccessIfModerator()) {
            return $this;
        }

        $this->pmWorkBulkUpdate();

        return $this;
    }


    #####
    public function pmWorkBulkUpdate($idSubtask = null)
    {
        _baseAlinaEloquentTransaction::begin();
        $this
            ->getParents($idSubtask)
            ->upsertPmWork()
        ;
        _baseAlinaEloquentTransaction::commit();

        return $this;
    }

    public function getParents($idSubTask = null)
    {
        $mSubtask      = $this;
        $mTask         = new pm_task();
        $mProject      = new pm_project;
        $mDepartment   = new pm_department();
        $mOrganization = new pm_organization();

        if (!empty($idSubTask)) {
            $mSubtask->getById($idSubTask);
        } else {
            $mSubtask->getById($mSubtask->id);
        }
        $mTask->getById($mSubtask->attributes->pm_task_id);
        $mProject->getById($mTask->attributes->pm_project_id);
        $mDepartment->getById($mProject->attributes->pm_department_id);
        $mOrganization->getById($mDepartment->attributes->pm_organization_id);

        $this->attributes->pm_task         = $mTask->attributes;
        $this->attributes->pm_project      = $mProject->attributes;
        $this->attributes->pm_department   = $mDepartment->attributes;
        $this->attributes->pm_organization = $mOrganization->attributes;

        return $this;
    }

    public function upsertPmWork()
    {
        #####
        #region CALCULATE WORK NAME
        $onh                      = $this->attributes->pm_organization->name_human;
        $dnh                      = $this->attributes->pm_department->name_human;
        $department_price_min     = $this->attributes->pm_department->price_min;
        $pnh                      = $this->attributes->pm_project->name_human;
        $project_price_multiplier = $this->attributes->pm_project->price_multiplier;
        $tnh                      = $this->attributes->pm_task->name_human;
        $stnh                     = $this->attributes->name_human;
        $subtask_time_estimated   = $this->attributes->time_estimated;

        $name_human = json_encode([
            'onh'                      => $onh,
            'dnh'                      => $dnh,
            'pnh'                      => $pnh,
            'tnh'                      => $tnh,
            'stnh'                     => $stnh,
            'department_price_min'     => $department_price_min,
            'project_price_multiplier' => $project_price_multiplier,
            'subtask_time_estimated'   => $subtask_time_estimated,
        ]);
        #endregion CALCULATE WORK NAME
        #####

        #####
        #region MATH CALCULATE WORK PRICE
        $pm_department_price_min     = $this->attributes->pm_department->price_min;
        $pm_project_price_multiplier = $this->attributes->pm_project->price_multiplier;
        $pm_subtask_time_estimated   = $this->attributes->time_estimated;
        $price_this_work             = $pm_department_price_min * $pm_project_price_multiplier * $pm_subtask_time_estimated;
        #endregion MATH CALCULATE WORK PRICE
        #####

        $dataWork = [
            'name_human'         => $name_human,
            'price_this_work'    => $price_this_work,
            'pm_organization_id' => $this->attributes->pm_organization->id,
            'pm_department_id'   => $this->attributes->pm_department->id,
            'pm_project_id'      => $this->attributes->pm_project->id,
            'pm_task_id'         => $this->attributes->pm_task->id,
            'pm_subtask_id'      => $this->attributes->id,
            'flag_archived'      => 0,
        ];

        $mWork = new pm_work();
        $mWork->upsertByUniqueFields($dataWork, [
            [
                'pm_organization_id',
                'pm_department_id',
                'pm_project_id',
                'pm_task_id',
                'pm_subtask_id',
                'flag_archived',
            ],
        ]);

        Message::setSuccess(implode(' ', [
            ___('Updated.'),
            ___('Work ID:'),
            $mWork->id,
            ___('New Work price:'),
            $price_this_work,
        ]));


        return $this;
    }
}
