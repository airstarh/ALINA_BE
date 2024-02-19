<?php

namespace alina\mvc\Model;

trait pm_trait
{
    public function created_by()
    {
        return [
            'created_by' => [
                'has'        => 'one',
                'multiple'   => false,
                'disabled'   => true,
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
                    ['leftJoin', 'user AS creator', 'creator.id', '=', "$this->alias.created_by"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'creator.firstname AS creator.firstname',
                            'creator.lastname AS creator.lastname',
                            'creator.mail AS creator.mail',
                            'creator.emblem AS creator.emblem',
                        ],
                    ],
                ],
            ],
        ];
    }

    ###
    public function modified_by()
    {
        return [
            'modified_by' => [
                'has'        => 'one',
                'multiple'   => false,
                'disabled'   => true,
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
                    ['leftJoin', 'user AS modifier', 'modifier.id', '=', "$this->alias.modified_by"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'modifier.firstname AS modifier.firstname',
                            'modifier.lastname AS modifier.lastname',
                            'modifier.mail AS modifier.mail',
                            'modifier.emblem AS modifier.emblem',
                        ],
                    ],
                ],
            ],
        ];
    }

    ###
    public function manager_id()
    {
        return [
            'manager_id' => [
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
        ];
    }

    ###
    public function assignee_id()
    {
        return [
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
        ];
    }

    ###
    public function _pm_department()
    {
        return [
            '_pm_department' => [
                'has'        => 'many',
                'multiple'   => true,
                'disabled'   => true,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_department',
                    'childPk'        => 'id',
                    'childGlueKey'   => 'pm_organization_id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS child', 'child.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'child.*',
                            'child.id AS child_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
        ];
    }

    ###
    public function _pm_project()
    {
        return [
            '_pm_project' => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS pm_department', 'pm_department.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'pm_project AS pm_project', 'pm_project.pm_department_id', '=', 'pm_department.id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_project.id AS _pm_project_id',
                            'pm_project.name_human AS _pm_project_name_human',
                            'pm_project.price_multiplier AS _pm_project_price_multiplier',
                            'pm_project.pm_department_id AS _pm_project_pm_department_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
        ];
    }

    ###
    public function _pm_task()
    {
        return [
            '_pm_task' => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS pm_department', 'pm_department.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'pm_project AS pm_project', 'pm_project.pm_department_id', '=', 'pm_department.id'],
                    ['join', 'pm_task AS pm_task', 'pm_task.pm_project_id', '=', 'pm_project.id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_task.id AS _pm_task_id',
                            'pm_task.name_human AS _pm_task_name_human',
                            'pm_task.pm_project_id AS _pm_task_pm_project_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
        ];
    }

    ###
    public function _pm_subtask()
    {
        return [
            '_pm_subtask' => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS pm_department', 'pm_department.pm_organization_id', '=', $this->qAliasPk()],
                    ['join', 'pm_project AS pm_project', 'pm_project.pm_department_id', '=', 'pm_department.id'],
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
                            ###'pm_task.id AS _pm_task_id',
                            'pm_task.name_human AS _pm_task_name_human',
                            ###'pm_subtask.id AS _pm_subtask_id',
                            'pm_subtask.name_human AS _pm_subtask_name_human',
                            "{$this->qAliasPk()} AS main_id",
                        ],
                    ],
                ],
                'orders'     => [
                    ['orderBy', 'pm_project.name_human', 'ASC'],
                    ['orderBy', 'pm_task.order_in_view', 'ASC'],
                    ['orderBy', 'pm_subtask.order_in_view', 'ASC'],
                    ['orderBy', 'pm_task.id', 'ASC'],
                    ['orderBy', 'pm_subtask.id', 'ASC'],
                ],
            ],
        ];
    }

    ###
    public function pm_organization_id()
    {
        return [
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
        ];
    }

    ###

    public function pm_department_id()
    {
        return [
            'pm_department_id' => [
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
                        ],
                    ],
                ],
            ],
        ];
    }

    ###
    public function pm_project_id()
    {
        return [
            'pm_project_id' => [
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
        ];
    }

    ###
    public function pm_task_id()
    {
        return [
            'pm_task_id' => [
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
        ];
    }
    ###
}