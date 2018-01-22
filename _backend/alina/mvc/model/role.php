<?php

namespace alina\mvc\model;

class role extends _baseAlinaEloquentModel
{
    public $table = 'role';

    public function fields()
    {
        return [
            'id'  => [],
            'name'  => [],
            'description'  => [],
            'active'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['name']
        ];
    }
}