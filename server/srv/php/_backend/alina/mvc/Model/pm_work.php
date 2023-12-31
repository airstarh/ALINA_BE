<?php

namespace alina\mvc\Model;

use alina\Message;

class pm_work extends _BaseAlinaModel
{
    public $table        = 'pm_work';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'                 => [],
            'name_human'         => [],
            'price_this_work'    => [],
            'pm_organization_id' => [],
            'pm_department_id'   => [],
            'pm_project_id'      => [],
            'pm_task_id'         => [],
            'pm_subtask_id'      => [],
            'flag_archived'      => ['default' => 0,],
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
                            'pm_organization.name_human AS _pm_organization_name_human',
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
                            'pm_department.price_min AS _pm_department_price_min',
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
                            'pm_project.name_human AS _pm_project_name_human',
                            'pm_project.price_multiplier AS _pm_project_price_multiplier',
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
            'pm_subtask_id'      => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_subtask',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                    'masterChildPk'  => 'pm_subtask_id',
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
                            'pm_subtask.name_human AS _pm_subtask_name_human',
                            'pm_subtask.time_estimated AS _pm_subtask_time_estimated',
                            'pm_subtask.price AS _pm_subtask_price',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }

    public function hookRightAfterSave()
    {
        $this->updateWorkDone($this->id);
    }

    public function updateWorkDone($idWork)
    {
        $this->getById($idWork);
        $m            = new pm_work_done();
        $listWorkDone = $m
            ->getAll([
                ['pm_work_id', '=', $idWork],
                ['flag_archived', '=', 0],
            ])
            ->toArray()
        ;
        foreach ($listWorkDone as $item) {
            /**
             * Other staff happens in hookRightBeforeSave of pm_work_done
             */
            (new pm_work_done())->updateById($item);
            Message::setSuccess($item->id);
        }

        return $this;
    }
    #####
}
