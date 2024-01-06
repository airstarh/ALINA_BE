<?php

namespace alina\mvc\Model;

class pm_work_done extends _BaseAlinaModel
{
    public $table        = 'pm_work_done';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'            => [],
            'pm_work_id'    => [],
            'assignee_id'   => [
                'default' => CurrentUser::id(),
            ],
            'amount'        => [],
            'price_final'   => [],
            'time_spent'    => [],
            'for_date'      => [],
            'flag_archived' => ['default' => 0,],
            'created_at'    => [],
            'created_by'    => [],
            'modified_at'   => [],
            'modified_by'   => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
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
            'pm_work_id'  => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_work',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'pm_work AS pm_work', 'pm_work.id', '=', "{$this->alias}.pm_work_id"],
                    ['leftJoin', 'pm_subtask AS pm_subtask', 'pm_subtask.id', '=', 'pm_work.pm_subtask_id'],
                    ['leftJoin', 'pm_task AS pm_task', 'pm_task.id', '=', 'pm_work.pm_task_id'],
                    ['leftJoin', 'pm_project AS pm_project', 'pm_project.id', '=', 'pm_work.pm_project_id'],
                    ['leftJoin', 'pm_department AS pm_department', 'pm_department.id', '=', 'pm_work.pm_department_id'],
                    ['leftJoin', 'pm_organization AS pm_organization', 'pm_organization.id', '=', 'pm_work.pm_organization_id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_work.name_human AS pm_work.name_human',
                            'pm_subtask.name_human AS pm_subtask.name_human',
                            'pm_task.name_human AS pm_task.name_human',
                            'pm_project.name_human AS pm_project.name_human',
                            'pm_department.name_human AS pm_department.name_human',
                            'pm_organization.name_human AS pm_organization.name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        $mWork = new pm_work();
        $mWork->getById($dataArray['pm_work_id']);
        $coef                     = $mWork->attributes->price_this_work;
        $dataArray['price_final'] = $dataArray['amount'] * $coef;

        $mDepartment = new pm_department();
        $mDepartment->getById($mWork->attributes->pm_department_id);
        $price_min = $mDepartment->attributes->price_min;

        $mProject = new pm_project();
        $mProject->getById($mWork->attributes->pm_project_id);
        $price_multiplier = $mProject->attributes->price_multiplier;

        $dataArray['time_spent'] = $dataArray['price_final'] / $price_min / $price_multiplier;

        return $this;
    }

    public function doArchive($id)
    {
        $this->getById($id);
        $mWork = new pm_work();
        $mWork->getById($this->attributes->pm_work_id);
    }
    #####
}
