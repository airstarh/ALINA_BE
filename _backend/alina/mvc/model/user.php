<?php

namespace alina\mvc\model;

class user extends _BaseAlinaModel
{
    public $table = 'user';

    public function fields()
    {
        return [
            'id'        => [],
            'mail'      => [
                'filters'    => [
                    // Could be a closure, string with function name or an array
                    'trim',
                    function ($v) {
                        return mb_strtolower($v);
                    },
                ],
                'validators' => [
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       => 'strlen',
                        'errorIf' => [FALSE, 0],
                        'msg'     => 'Email is required!',
                    ],
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       =>
                            function ($v) {
                                return filter_var($v, FILTER_VALIDATE_EMAIL);
                            },
                        'errorIf' => [FALSE, 0],
                        'msg'     => 'Invalid Email Address!',
                    ],

                ],
            ],
            'firstname' => [],
            'lastname'  => [],
            'active'    => [],
            'verified'  => [],
            'created'   => [],
            'lastenter' => [],
            'picture'   => [],
            'timezone'  => [],
            'password'  => [
                'filters'    => [
                    // Could be a closure, string with function name or an array
                    'trim',
                    function ($v) {
                        //ToDo: SALT
                        return md5($v);
                    },

                ],
                'validators' => [
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       => 'strlen',
                        'errorIf' => [FALSE, 0],
                        'msg'     => 'Email is required!',
                    ],
                ],

            ],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['mail'],
        ];
    }

    public function referencesTo()
    {
        return [
            'roles'    => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'rbac_user_role AS glue', 'glue.user_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'rbac_role AS child', 'child.id', '=', 'glue.role_id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.*', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],

            ],
            'permissions'    => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'rbac_user_role AS glue', 'glue.user_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'rbac_role_permission AS glue2', 'glue2.role_id', '=', 'glue.role_id'],
                    ['join', 'rbac_permission AS child', 'child.id', '=', 'glue2.permission_id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.*', 'glue.id AS ref_id', 'glue2.id AS ref_id2', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],

            ],
            'timezone' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'timezone AS child', 'child.id', '=', "{$this->alias}.timezone"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.name AS timezone_name']],
                ],
            ],
            'files'    => [
                'has'        => 'many',
                'model'      => 'file',
                'joins'      => [
                    ['join', 'file AS child', 'child.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                ],
                'conditions' => [
                    ['where', 'child.entity_table', '=', $this->table],
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
            ],
            'tags'     => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'tag_to_entity AS glue', 'glue.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'tag AS child', 'child.id', '=', 'glue.tag_id'],
                ],
                'conditions' => [
                    ['where', 'glue.entity_table', '=', $this->table],
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
                'orders'     => [
                    ['orderBy', 'child.name', 'ASC'],
                ],
            ],
        ];
    }
}
