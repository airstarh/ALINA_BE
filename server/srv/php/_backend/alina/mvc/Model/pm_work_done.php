<?php

namespace alina\mvc\Model;

class pm_work_done extends _BaseAlinaModel
{
    public $table = 'pm_work_done';

    public function fields()
    {
        return [
            'id'           => [],
            'pm_work_id'   => [],
            'assignee_id'  => [],
            'amount'       => [],
            'time_spent'   => [],
            'price_result' => [],
            'created_at'   => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field ######
            'assignee' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS assignee', 'assignee.id', '=', "{$this->alias}.assignee_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect', [
                        'assignee.firstname AS assignee_firstname',
                        'assignee.lastname AS assignee_lastname',
                        'assignee.emblem AS assignee_emblem',
                    ],
                    ],
                ],
            ],
            ##### field ######
            'pm_work'  => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'pm_work AS pm_work', 'pm_work.id', '=', "{$this->alias}.pm_work_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_work.name_human AS pm_work_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######

        ];
    }
    #####
}
