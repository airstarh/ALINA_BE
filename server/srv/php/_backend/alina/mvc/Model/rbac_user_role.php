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
}