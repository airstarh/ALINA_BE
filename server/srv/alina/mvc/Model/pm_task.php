<?php

namespace alina\mvc\Model;

class pm_task extends _BaseAlinaModel
{
    use pm_trait;

    public $table        = 'pm_task';
    public $addAuditInfo = true;
    public $sortDefault  = [["order_in_view", 'ASC'], ["id", 'ASC']];

    public function fields()
    {
        return [
            'id'            => [],
            'name_human'    => [
                'required' => true,
            ],
            'order_in_view' => ['default' => 0],
            'pm_project_id' => [],
            'manager_id'    => [],
            'assignee_id'   => [],
            'completed_at'  => [],
            'status'        => [],
            'created_at'    => [],
            'created_by'    => [],
            'modified_at'   => [],
            'modified_by'   => [],

        ];
    }

    #####
    public function referencesTo()
    {

        return array_merge([],
            $this->manager_id(),
            $this->assignee_id(),
            $this->pm_project_id(),
            [
                '_children' => [
                    'has'        => 'many',
                    ##############################
                    # for Select With References
                    'joins'      => [
                        ['join', 'pm_subtask AS pm_subtask', 'pm_subtask.pm_task_id', '=', $this->qAliasPk()],
                    ],
                    'conditions' => [],
                    'addSelects' => [
                        [
                            'addSelect',
                            [
                                'pm_subtask.id AS _pm_subtask_id',
                                'pm_subtask.name_human AS _pm_subtask_name_human',
                                'pm_subtask.time_estimated AS _pm_subtask_time_estimated',
                                'pm_subtask.order_in_view AS _pm_subtask_order_in_view',
                                "{$this->alias}.{$this->pkName} AS main_id",
                            ],
                        ],
                    ],
                    'orders'     => [
                        ['orderBy', 'pm_subtask.order_in_view', 'ASC'],
                        ['orderBy', 'pm_subtask.id', 'ASC'],
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
            $m->pmWorkBulkUpdate($id);
        }
        return $this;
    }
    #####
}
