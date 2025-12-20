<?php

namespace alina\mvc\Model;

use alina\Message;

class pm_work extends _BaseAlinaModel
{
    use pm_trait;

    public $table        = 'pm_work';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'                 => [],
            'name_human'         => [
                'type' => 'readonly',
            ], /*calculation*/
            'price_this_work'    => [
                'type' => 'readonly',
            ], /*calculation*/
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
        return array_merge([],
            [
                ##### field ######
                'pm_organization_id'        => [
                    'disabled'   => true,
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
                'pm_department_id'          => [
                    'disabled'   => true,
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
                'pm_project_id'             => [
                    'disabled'   => true,
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
                'pm_task_id'                => [
                    'disabled'   => true,
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
                'pm_subtask_id'             => [
                    'disabled'   => true,
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
                '_pm_work_done_notarchived' => [
                    ///'disabled'   => true,
                    'has'        => 'many',
                    'multiple'   => true,
                    'type'       => 'readonly',
                    ##############################
                    # for Select With References
                    'joins'      => [
                        ['leftJoin', 'pm_work_done AS child', 'child.pm_work_id', '=', "$this->alias.$this->pkName"],
                    ],
                    'conditions' => [
                        ['where', 'child.flag_archived', '=', 0],
                    ],
                    'addSelects' => [
                        [
                            'addSelect',
                            [
                                "$this->alias.$this->pkName AS main_id",
                                'child.id AS child_id',
                                'child.assignee_id AS pm_work_done.assignee_id',
                                'child.amount AS pm_work_done.amount',
                                'child.for_date AS pm_work_done.for_date',
                                'child.price_final AS pm_work_done.price_final',
                                'child.time_spent AS pm_work_done.time_spent',
                                'child.time_spent AS pm_work_done.time_spent',
                            ],
                        ],
                    ],
                ],
                ##### field ######
            ],
            $this->created_by(),
            $this->modified_by()
        );
    }

    #####
    public function hookRightBeforeSave(&$dataArray)
    {
        $mO  = new pm_organization();
        $mD  = new pm_department();
        $mP  = new pm_project();
        $mT  = new pm_task();
        $mSt = new pm_subtask();

        $mO->getById($dataArray['pm_organization_id']);
        $mD->getById($dataArray['pm_department_id']);
        $mP->getById($dataArray['pm_project_id']);
        $mT->getById($dataArray['pm_task_id']);
        $mSt->getById($dataArray['pm_subtask_id']);

        #####
        # NAME HUMAN
        $onh  = $mO->attributes->name_human;
        $dnh  = $mD->attributes->name_human;
        $pnh  = $mP->attributes->name_human;
        $tnh  = $mT->attributes->name_human;
        $stnh = $mSt->attributes->name_human;

        $department_price_min     = $mD->attributes->price_min;
        $project_price_multiplier = $mP->attributes->price_multiplier;
        $subtask_time_estimated   = $mSt->attributes->time_estimated;

        $dataArray['name_human'] = $this->calcNameHuman($onh, $dnh, $pnh, $tnh, $stnh, $department_price_min, $project_price_multiplier, $subtask_time_estimated);

        #####
        # PRICE THIS WORK
        $pm_department_price_min      = $department_price_min;
        $pm_project_price_multiplier  = $project_price_multiplier;
        $pm_subtask_time_estimated    = $subtask_time_estimated;
        $dataArray['price_this_work'] = $this->calcPriceThisWork($pm_department_price_min, $pm_project_price_multiplier, $pm_subtask_time_estimated);

        return $this;
    }

    public function hookRightAfterSave()
    {
        $this->pmWorkDoneBulkUpdate($this->id);
    }

    public function pmWorkDoneBulkUpdate($idWork = null)
    {
        if (!empty($idWork)) {
            $this->getById($idWork);
        }
        else {
            $this->getById($this->id);
        }

        if ($this->attributes->flag_archived == 0) {
            $mWd          = new pm_work_done();
            $listWorkDone = $mWd
                ->getAll([
                    ['pm_work_id', '=', $idWork],
                    ['flag_archived', '=', 0],
                ])
                ->toArray()
            ;
            if (!empty($listWorkDone)) {
                $counterUpdated = [];
                foreach ($listWorkDone as $item) {
                    /**
                     * Other staff happens in hookRightBeforeSave of pm_work_done
                     */
                    $counterUpdated[] = $item->id;
                    (new pm_work_done())->updateById($item);
                }
                Message::setSuccess(implode(' ', [
                    ___('Updated'),
                    ___('Work ID:'),
                    $this->id,
                    ___('New Work price:'),
                    $this->attributes->price_this_work,
                    ___('Updated Done Works:'),
                    count($counterUpdated),
                ]));
            }
        }

        return $this;
    }

    public function getParents($idWork = null)
    {
        $mWork         = $this;
        $mSubtask      = new pm_subtask();
        $mTask         = new pm_task();
        $mProject      = new pm_project;
        $mDepartment   = new pm_department();
        $mOrganization = new pm_organization();

        if (!empty($idWork)) {
            $mWork->getById($idWork);
        }
        else {
            $mWork->getById($mWork->id);
        }
        $mSubtask->getById($mWork->attributes->pm_subtask_id);
        $mTask->getById($mWork->attributes->pm_task_id);
        $mProject->getById($mWork->attributes->pm_project_id);
        $mDepartment->getById($mWork->attributes->pm_department_id);
        $mOrganization->getById($mWork->attributes->pm_organization_id);

        $this->attributes->pm_subtask      = $mSubtask->attributes;
        $this->attributes->pm_task         = $mTask->attributes;
        $this->attributes->pm_project      = $mProject->attributes;
        $this->attributes->pm_department   = $mDepartment->attributes;
        $this->attributes->pm_organization = $mOrganization->attributes;

        return $this;
    }

    public function calcPriceThisWork(
        $pm_department_price_min,
        $pm_project_price_multiplier,
        $pm_subtask_time_estimated
    )
    {
        return $pm_department_price_min * $pm_project_price_multiplier * $pm_subtask_time_estimated;
    }

    public function calcNameHuman(
        $onh,
        $dnh,
        $pnh,
        $tnh,
        $stnh,
        $department_price_min,
        $project_price_multiplier,
        $subtask_time_estimated
    )
    {
        return json_encode([
            'onh'                      => $onh,
            'dnh'                      => $dnh,
            'pnh'                      => $pnh,
            'tnh'                      => $tnh,
            'stnh'                     => $stnh,
            'department_price_min'     => $department_price_min,
            'project_price_multiplier' => $project_price_multiplier,
            'subtask_time_estimated'   => $subtask_time_estimated,
        ]);
    }
    #####
}
