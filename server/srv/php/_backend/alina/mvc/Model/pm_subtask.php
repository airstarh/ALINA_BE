<?php

namespace alina\mvc\Model;

class pm_subtask extends _BaseAlinaModel
{
    public $table = 'pm_subtask';

    public function fields()
    {
        return [
            'id'             => [],
            'name_human'     => [],
            'time_estimated' => [],
            'price'          => [],
            'manager_id'     => [],
            'assignee_id'    => [],
            'created_at'     => [],
            'completed_at'   => [],
            'status'         => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field ######
            'manager' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS manager', 'manager.id', '=', "{$this->alias}.manager_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'manager.firstname AS manager_firstname',
                            'manager.lastname AS manager_lastname',
                            'manager.emblem AS manager_emblem',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }
    #####
}
