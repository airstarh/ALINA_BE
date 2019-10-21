<?php

namespace alina\mvc\model;

use alina\message;
use alina\utils\Data;
use alina\utils\Sys;

class user extends _BaseAlinaModel
{
    public $table = 'user';

    public function fields()
    {
        $fDefault = parent::fields();
        $fCustom  = [
            'id'          => [],
            'mail'        => [
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
            'firstname'   => [],
            'lastname'    => [],
            'is_deleted'  => [],
            'is_verified' => [],
            'date_int_created'     => [
                'default' => ALINA_TIME,
            ],
            'date_int_lastenter'   => [],
            'file_picture'     => [],
            'timezone'    => [],
            'password'    => [
                'filters'    => [
                    // Could be a closure, string with function name or an array
                    'trim',
                ],
                'validators' => [
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       => 'strlen',
                        'errorIf' => [FALSE, 0],
                        'msg'     => 'Password cannot be empty',
                    ],
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       => function ($v) {
                            return \alina\utils\Str::lessThan($v, 8);
                        },
                        'errorIf' => [TRUE],
                        'msg'     => 'Password length cannot be less than 8 symbols',
                    ],
                ],

            ],
            'date_int_banned_till' => [],
            'ip'          => [
                'default' => Sys::getUserIp(),
            ],
            'language'    => [
                'default' => Sys::getUserLanguage(),
            ],
        ];
        $fRes     = array_merge($fDefault, $fCustom);

        return $fRes;
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
            'rbac_user_role'  => [
                'keyBy'      => 'id', //ToDo: Hardcoded, not involved
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'rbac_user_role AS glue', 'glue.user_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'rbac_role AS child', 'child.id', '=', 'glue.role_id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],

            ],
            'rbac_permission' => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'rbac_user_role AS glue', 'glue.user_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'rbac_role_permission AS glue2', 'glue2.role_id', '=', 'glue.role_id'],
                    ['join', 'rbac_permission AS child', 'child.id', '=', 'glue2.permission_id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', 'glue.id AS ref_id', 'glue2.id AS ref_id2', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],

            ],
            'timezone'        => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'timezone AS child', 'child.id', '=', "{$this->alias}.timezone"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.name AS timezone_name']],
                ],
            ],
            'file'            => [
                'has'        => 'many',
                'model'      => 'file',
                'joins'      => [
                    ['join', 'file AS child', 'child.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                ],
                'conditions' => [
                    ['where', 'child.entity_table', '=', $this->table],
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
            ],
            'tag'             => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'tag_to_entity AS glue', 'glue.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'tag AS child', 'child.id', '=', 'glue.tag_id'],
                ],
                'conditions' => [
                    ['where', 'glue.entity_table', '=', $this->table],
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
                'orders'     => [
                    ['orderBy', 'child.name', 'ASC'],
                ],
            ],
        ];
    }

    public function referencesSources()
    {
        return [
            'rbac_user_role' => [
                'model'      => 'rbac_role',
                'keyBy'      => 'id',
                'human_name' => ['name'],
                'multiple'   => 'multiple',
                ####
                'thisKey'    => 'user_id',
                'thatKey'    => 'role_id',
            ],
            'timezone'       => [
                'model'      => 'timezone',
                'keyBy'      => 'id',
                'human_name' => ['name'],
                'multiple'   => '',
            ],
        ];
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        if (!isset($dataArray['password'])) {
            return $this;
        }
        if (!Data::isValidMd5($dataArray['password'])) {
            $dataArray['password'] = md5($dataArray['password']);
        }

        return $this;
    }

    #####
    public function hookRightAfterSave($data)
    {
        $referencesSources = $this->referencesSources();
        foreach ($referencesSources as $cfgName => $srcCfg) {
            if (isset($srcCfg['multiple']) && !empty($srcCfg['multiple'])) {
                if (isset($data->{$cfgName}) && !empty($data->{$cfgName})) {
                    $m = modelNamesResolver::getModelObject($cfgName);
                    $m->delete([
                        [$srcCfg['thisKey'], '=', $data->{$this->pkName}]]);
                    foreach ($data->{$cfgName} as $thatKey) {
                        $m->insert([
                            $srcCfg['thisKey'] => $data->{$this->pkName},
                            $srcCfg['thatKey'] => $thatKey,
                        ]);
                    }
                }
            }
        }

        return $this;
    }
    #####

}
