<?php

namespace alina\mvc\model;

class user extends _baseAlinaEloquentModel
{
    public $table = 'user';

    public function fields()
    {
        return [
            'id'        => [],
            'mail'      => [],
            'firstname' => [],
            'lastname'  => [],
            'active'    => [],
            'verified'  => [],
            'created'   => [],
            'lastenter' => [],
            'picture'   => [],
            'timezone'  => [],
            'password'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['mail']
        ];
    }

    public function referencesTo()
    {
        return [
            'roles'    => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'user_role AS glue', 'glue.user_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'role AS child', 'child.id', '=', 'glue.role_id']
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.*', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]]
                ],

            ],
            'timezone' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'timezone AS child', 'child.id', '=', "{$this->alias}.timezone"]
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.name AS timezone_name']]
                ],
            ],
            'files'    => [
                'has'        => 'many',
                'joins'      => [
                    ['join', 'file AS child', 'child.entity_id', '=', "{$this->alias}.{$this->pkName}"]
                ],
                'conditions' => [
                    ['where', 'child.entity_table', '=', $this->table]
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', "{$this->alias}.{$this->pkName} AS main_id"]]
                ],
            ],
            'tags'     => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['join', 'tag_to_entity AS glue', 'glue.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'tag AS child', 'child.id', '=', 'glue.tag_id']
                ],
                'conditions' => [
                    ['where', 'glue.entity_table', '=', $this->table]
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]]
                ],
            ],
        ];
    }
}