<?php

namespace alina\mvc\Model;

class pm_task extends _BaseAlinaModel
{
    public $table        = 'pm_task';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'            => [],
            'name_human'    => [],
            'pm_project_id' => [],
            'manager_id'    => [],
            'assignee_id'   => [],
            'created_at'    => [],
            'completed_at'  => [],
            'status'        => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'manager_id'    => [
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
            ##### field #####
            'assignee_id'   => [
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
            ##### field #####
            'pm_project_id' => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_project',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                    'masterChildPk'  => 'pm_project_id',
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
            ##### field #####
            '_children'     => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_subtask AS pm_subtask', 'pm_subtask.pm_task_id', '=', "{$this->alias}.{$this->pkName}"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
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
        if (empty($this->attributes->_children)) return $this;
        foreach ($this->attributes->_children as $child) {
            $id = $child->_pm_subtask_id;
            $m  = new pm_subtask();
            $m->bulkUpdate($id);
        }
        return $this;
    }
    #####
}
