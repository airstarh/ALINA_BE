<?php

namespace alina\mvc\model;

class role extends _BaseAlinaModel
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