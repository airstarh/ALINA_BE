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
                    'masterChildPk'  => 'manager_id',
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
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_work.name_human AS pm_work.name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }
    #####
}
