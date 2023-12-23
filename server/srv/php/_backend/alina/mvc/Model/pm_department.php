<?php

namespace alina\mvc\Model;

class pm_department extends _BaseAlinaModel
{
    public $table        = 'pm_department';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'                 => [],
            'name_human'         => [],
            'pm_organization_id' => [],
            'price_min'          => [
                'default' => 1,
            ],
            'manager_id'         => [],
            'created_at'         => [],
            'created_by'         => [],
            'modified_at'        => [],
            'modified_by'        => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'manager_id'         => [
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
            'pm_organization_id' => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_organization',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                    'masterChildPk'  => 'pm_organization_id',
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
            ##### field #####
            '_children'          => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_project AS pm_project', 'pm_project.pm_department_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'pm_task AS pm_task', 'pm_task.pm_project_id', '=', 'pm_project.id'],
                    ['join', 'pm_subtask AS pm_subtask', 'pm_subtask.pm_task_id', '=', 'pm_task.id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_project.id AS _pm_project_id',
                            'pm_project.name_human AS _pm_project_name_human',
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
