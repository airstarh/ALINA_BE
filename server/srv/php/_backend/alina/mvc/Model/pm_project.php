<?php

namespace alina\mvc\Model;

class pm_project extends _BaseAlinaModel
{
    use pm_trait;

    public $table        = 'pm_project';
    public $addAuditInfo = true;


    public function fields()
    {
        return [
            'id'               => [],
            'name_human'       => [
                'required' => true,
            ],
            'pm_department_id' => [],
            'price_multiplier' => [
                'default' => 1,
            ],
            'manager_id'       => [],
            'assignee_id'      => [],
            'completed_at'     => [],
            'status'           => [],
            'created_at'       => [],
            'created_by'       => [],
            'modified_at'      => [],
            'modified_by'      => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return array_merge([],
            $this->pm_department_id(),
            $this->manager_id(),
            $this->assignee_id(),

            [
                ##### field #####
                '_children' => [
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
            ],
            $this->created_by(),
            $this->modified_by(),
        );
    }

    public function hookRightAfterSave($data)
    {
        //if (!AlinaAccessIfAdmin() && !AlinaAccessIfModerator()) {
        //    return $this;
        //}

        $this->mWorkBulkUpdate();

        return $this;
    }

    public function mWorkBulkUpdate()
    {
        $mWork    = new pm_work();
        $listWork = $mWork->getAll([
            ['pm_project_id', '=', $this->id],
            ['flag_archived', '=', 0],
        ])->toArray();
        foreach ($listWork as $item) {
            unset($item->name_human);
            unset($item->price_this_work);
            (new pm_work())->updateById($item);
        }
        return $this;
    }
    #####
}
