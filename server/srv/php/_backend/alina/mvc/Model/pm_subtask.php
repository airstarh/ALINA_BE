<?php

namespace alina\mvc\Model;

class pm_subtask extends _BaseAlinaModel
{
    public $table        = 'pm_subtask';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'             => [],
            'name_human'     => [],
            'manager_id'     => [],
            'assignee_id'    => [],
            'pm_task_id'     => [],
            'time_estimated' => [],
            'price'          => [],
            'created_at'     => [],
            'completed_at'   => [],
            'status'         => [],
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
                            'manager.firstname AS _manager_firstname',
                            'manager.lastname AS _manager_lastname',
                            'manager.mail AS _manager_mail',
                            'manager.emblem AS _manager_emblem',
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
                            'assignee.firstname AS _assignee_firstname',
                            'assignee.lastname AS _assignee_lastname',
                            'assignee.mail AS _assignee_mail',
                            'assignee.emblem AS _assignee_emblem',
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
                            'pm_task.name_human AS _pm_task_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }

    #####
    public function getListOfParents()
    {
        $pm_subtask      = $this;
        $pm_task         = new pm_task();
        $pm_project      = new pm_project;
        $pm_department   = new pm_department();
        $pm_organization = new pm_organization();
        $pm_work         = new pm_work();

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
}
