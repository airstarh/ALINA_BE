<?php

namespace alina\mvc\Model;
class rbac_user_role extends _BaseAlinaModel
{
    public $table = 'rbac_user_role';

    public function fields()
    {
        return [
            'id'      => [],
            'user_id' => [],
            'role_id' => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['user_id', 'role_id'],
        ];
    }

    public function referencesTo()
    {
        return [
            '_user' => [
                'has'        => 1,
                'joins'      => [
                    ['leftJoin', 'user AS u', 'u.id', '=', "{$this->alias}.user_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['u.firstname AS _user_first_name']],
                ],
            ],
            '_role' => [
                'has'        => 1,
                'joins'      => [
                    ['leftJoin', 'rbac_role AS r', 'r.id', '=', "{$this->alias}.role_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['r.name AS _role_name']],
                ],
            ]
        ];
    }

}