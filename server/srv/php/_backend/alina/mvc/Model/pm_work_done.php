<?php

namespace alina\mvc\Model;

class pm_work_done extends _BaseAlinaModel
{
    public $table        = 'pm_work_done';
    public $addAuditInfo = true;

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
                            'assignee.firstname AS _assignee_firstname',
                            'assignee.lastname AS _assignee_lastname',
                            'assignee.mail AS _assignee_mail',
                            'assignee.emblem AS _assignee_emblem',
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
                    'childHumanName' => ['id'],
                    'masterChildPk'  => 'pm_work_id',
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
                            //'pm_work.name_human AS pm_work_name_human',
                        ],
                    ],
                ],
            ],
            ##### field ######
        ];
    }
    #####
}
