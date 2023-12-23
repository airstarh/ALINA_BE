<?php

namespace alina\mvc\Model;

use alina\Message;
use alina\Utils\Data;

class pm_subtask extends _BaseAlinaModel
{
    public $table        = 'pm_subtask';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'             => [],
            'name_human'     => [],
            'time_estimated' => [],
            'pm_task_id'     => [
                'default' => 1,
            ],

            'price'        => [],
            'manager_id'   => [],
            'assignee_id'  => [],
            'completed_at' => [],
            'status'       => [],
            'created_at'   => [],
            'created_by'   => [],
            'modified_at'  => [],
            'modified_by'  => [],
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
                    'masterChildPk'  => 'manager_id',
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
                    'masterChildPk'  => 'manager_id',
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
                    'masterChildPk'  => 'pm_task_id',
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

        $this->bulkUpdate();

        return $this;
    }


    #####
    public function bulkUpdate($id = null)
    {
        _baseAlinaEloquentTransaction::begin();
        $this
            ->getListOfParents($id)
            ->upsertPmWork()
        ;
        _baseAlinaEloquentTransaction::commit();

        return $this;
    }

    public function getListOfParents($id = null)
    {
        $pm_subtask      = $this;
        $pm_task         = new pm_task();
        $pm_project      = new pm_project;
        $pm_department   = new pm_department();
        $pm_organization = new pm_organization();

        if (!empty($id)) $pm_subtask->getOneWithReferences([["$pm_subtask->alias.id", '=', $id]]);
        $pm_task->getOneWithReferences([["$pm_task->alias.id", '=', $pm_subtask->attributes->pm_task_id]]);
        $pm_project->getOneWithReferences([["$pm_project->alias.id", '=', $pm_task->attributes->pm_project_id]]);
        $pm_department->getOneWithReferences([["$pm_department->alias.id", '=', $pm_project->attributes->pm_department_id]]);
        $pm_organization->getOneWithReferences([["$pm_organization->alias.id", '=', $pm_department->attributes->pm_organization_id]]);

        $this->attributes->pm_task         = $pm_task->attributes;
        $this->attributes->pm_project      = $pm_project->attributes;
        $this->attributes->pm_department   = $pm_department->attributes;
        $this->attributes->pm_organization = $pm_organization->attributes;

        return $this;
    }

    public function upsertPmWork()
    {
        #####
        #region CALCULATE WORK NAME
        $name_human = implode(' | ', [
            $this->attributes->pm_department->name_human,
            $this->attributes->pm_department->price_min,
            $this->attributes->pm_project->name_human,
            $this->attributes->pm_project->price_multiplier,
            $this->attributes->pm_task->name_human,
            $this->attributes->name_human,
            $this->attributes->time_estimated,
        ]);
        #endregion CALCULATE WORK NAME
        #####

        #####
        #refion MATH CALCULATE WORK PRICE
        $pm_department_price_min     = $this->attributes->pm_department->price_min;
        $pm_project_price_multiplier = $this->attributes->pm_project->price_multiplier;
        $pm_subtask_time_estimated   = $this->attributes->time_estimated;
        $price_this_work             = $pm_department_price_min * $pm_project_price_multiplier * $pm_subtask_time_estimated;
        #endrefion MATH CALCULATE WORK PRICE
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
            ___('SubTask ID:'),
            $this->id,
            $this->attributes->name_human,
            ___('Work ID:'),
            $mWork->id,
            $mWork->attributes->name_human,
            ___('New Work price:'),
            $price_this_work,
        ]));

        return $this;
    }
}
