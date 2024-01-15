<?php

namespace alina\mvc\Model;

use alina\Message;
use alina\Utils\Data;

class pm_subtask extends _BaseAlinaModel
{
    use pm_trait;

    public $table        = 'pm_subtask';
    public $addAuditInfo = true;
    public $sortDefault  = [["order_in_view", 'ASC'], ["pm_task_id", 'ASC'], ["id", 'ASC']];

    public function fields()
    {
        return [
            'id'             => [],
            'name_human'     => [
                'required' => true,
            ],
            'time_estimated' => [],
            'order_in_view'  => ['default' => 0],
            'pm_task_id'     => ['default' => 1,],
            //'manager_id'     => [],
            //'assignee_id'    => [],
            //'price'          => [],
            //'completed_at'   => [],
            //'status'         => [],
            'created_at'     => [],
            'created_by'     => [],
            'modified_at'    => [],
            'modified_by'    => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return array_merge([],
            $this->manager_id(),
            $this->assignee_id(),
            $this->pm_task_id(),
            [
                ##### field ######
            ],
            $this->created_by(),
            $this->modified_by()
        );
    }

    public function hookRightAfterSave($data)
    {
        //if (!AlinaAccessIfAdmin() && !AlinaAccessIfModerator()) {
        //    return $this;
        //}

        $this->pmWorkBulkUpdate();

        return $this;
    }


    #####
    public function pmWorkBulkUpdate($idSubtask = null)
    {
        _baseAlinaEloquentTransaction::begin();
        $this
            ->getParents($idSubtask)
            ->upsertPmWork()
        ;
        _baseAlinaEloquentTransaction::commit();

        return $this;
    }

    public function getParents($idSubTask = null)
    {
        $mSubtask      = $this;
        $mTask         = new pm_task();
        $mProject      = new pm_project;
        $mDepartment   = new pm_department();
        $mOrganization = new pm_organization();

        if (!empty($idSubTask)) {
            $mSubtask->getById($idSubTask);
        }
        else {
            $mSubtask->getById($mSubtask->id);
        }
        $mTask->getById($mSubtask->attributes->pm_task_id);
        $mProject->getById($mTask->attributes->pm_project_id);
        $mDepartment->getById($mProject->attributes->pm_department_id);
        $mOrganization->getById($mDepartment->attributes->pm_organization_id);

        $this->attributes->pm_task         = $mTask->attributes;
        $this->attributes->pm_project      = $mProject->attributes;
        $this->attributes->pm_department   = $mDepartment->attributes;
        $this->attributes->pm_organization = $mOrganization->attributes;

        return $this;
    }

    public function upsertPmWork()
    {
        $mWork    = new pm_work();
        $dataWork = [
            'pm_organization_id' => $this->attributes->pm_organization->id,
            'pm_department_id'   => $this->attributes->pm_department->id,
            'pm_project_id'      => $this->attributes->pm_project->id,
            'pm_task_id'         => $this->attributes->pm_task->id,
            'pm_subtask_id'      => $this->attributes->id,
            'flag_archived'      => 0,
        ];


        $mWork->upsertByUniqueFields($dataWork, [
            [
                'pm_organization_id',
                'pm_department_id',
                'pm_project_id',
                'pm_task_id',
                'pm_subtask_id',
                'flag_archived',
            ],
        ]);

        return $this;
    }
}
