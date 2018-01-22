<?php

namespace alina\mvc\model;

class user_role extends _baseAlinaEloquentModel
{
    public $table = 'user_role';

    public function fields()
    {
        return [
            'id'  => [],
            'user_id'  => [],
            'role_id'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['user_id', 'role_id']
        ];
    }
}