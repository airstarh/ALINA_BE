<?php

namespace alina\mvc\Model;

class pm_work extends _BaseAlinaModel
{
    public $table = 'pm_work';

    public function fields()
    {
        return [
            'id'                 => [],
            'pm_organization_id' => [],
            'pm_department_id'   => [],
            'pm_project_id'      => [],
            'pm_task_id'         => [],
            'pm_subtask_id'      => [],
            'price_this_project' => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field ######
            'pm_organization' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'pm_organization AS pm_organization', 'pm_organization.id', '=', "{$this->alias}.pm_organization_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_organization.name_human AS pm_organization_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_department'   => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'pm_department AS pm_department', 'pm_department.id', '=', "{$this->alias}.pm_department_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_department.name_human AS pm_department_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_project'      => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'pm_project AS pm_project', 'pm_project.id', '=', "{$this->alias}.pm_project_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_project.name_human AS pm_project_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_task'         => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'pm_task AS pm_task', 'pm_task.id', '=', "{$this->alias}.pm_task_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_task.name_human AS pm_task_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
            'pm_subtask'      => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'pm_subtask AS pm_subtask', 'pm_subtask.id', '=', "{$this->alias}.pm_subtask_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_subtask.name_human AS pm_subtask_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }
    #####
}
