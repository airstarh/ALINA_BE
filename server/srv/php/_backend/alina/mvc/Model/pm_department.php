<?php

namespace alina\mvc\Model;

class pm_department extends _BaseAlinaModel
{
    public $table = 'pm_department';

    public function fields()
    {
        return [
            'id'                 => [],
            'name_human'         => [],
            'pm_organization_id' => [],
            'manager_id'         => [],
            'price_min'          => [
                'default' => 1,
            ],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'manager_id'         => [
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
            'pm_organization_id' => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_organization',
                    'childPk'        => 'id',
                    'childHumanName' => ['name_human'],
                    'masterChildPk'  => 'pm_organization_id',
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
                            'pm_organization.name_human AS _pm_organization_name_human',
                        ],
                    ],
                ],
            ],
            ##### field #####
        ];
    }
    #####
}
