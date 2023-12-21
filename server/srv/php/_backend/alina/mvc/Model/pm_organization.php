<?php

namespace alina\mvc\Model;

class pm_organization extends _BaseAlinaModel
{
    public $table        = 'pm_organization';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'         => [],
            'name_human' => [
                'default' => 'Организация',
            ],
            'manager_id' => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'manager_id'    => [
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
                    ['leftJoin', 'user AS manager', 'manager.id', '=', "{$this->alias}.manager_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'manager.firstname AS _manager_firstname',
                            'manager.lastname AS _manager_lastname',
                            'manager.mail AS _manager_mail',
                            'manager.emblem AS _manager_emblem',
                        ],
                    ],
                ],
            ],
            ##### field #####
            'pm_department' => [
                'has'        => 'many',
                'multiple'   => true,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_department',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                    'masterChildPk'  => 'id',
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS child', 'child.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'child.*',
                            'child.id AS child_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
            ##### field #####
        ];
    }
    #####
}
