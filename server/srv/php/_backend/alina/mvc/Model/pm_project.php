<?php

namespace alina\mvc\Model;

class pm_project extends _BaseAlinaModel
{
    public $table        = 'pm_project';
    public $addAuditInfo = true;


    public function fields()
    {
        return [
            'id'               => [],
            'name_human'       => [],
            'pm_department_id' => [],
            'price_multiplier' => [
                'default' => 1,
            ],
            'manager_id'       => [],
            'assignee_id'      => [],
            'created_at'       => [],
            'completed_at'     => [],
            'status'           => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'pm_department_id' => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_department',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                    'masterChildPk'  => 'pm_department_id',
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
                            'pm_department.name_human AS _pm_department_name_human',
                        ],
                    ],
                ],
            ],
            ##### field #####
            'manager_id'       => [
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
            ##### field #####
            'assignee_id'      => [
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
            ##### field #####
            '_children'        => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_task AS pm_task', 'pm_task.pm_project_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'pm_subtask AS pm_subtask', 'pm_subtask.pm_task_id', '=', 'pm_task.id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_task.id AS _pm_task_id',
                            'pm_task.name_human AS _pm_task_name_human',
                            'pm_subtask.id AS _pm_subtask_id',
                            'pm_subtask.name_human AS _pm_subtask_name_human',
                            'pm_subtask.time_estimated AS _pm_subtask_time_estimated',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
            ##### field #####
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

    public function bulkUpdate()
    {
        foreach ($this->attributes->_children as $child) {
            $id = $child->_pm_subtask_id;
            $m  = new pm_subtask();
            $m->bulkUpdate($id);
        }
        return $this;
    }
    #####
}
