<?php

namespace alina\mvc\model;

class timezone extends _BaseAlinaModel
{
    public $table  = 'timezone';
    public $pkName = 'id';

    public function fields()
    {
        return [
            'id'          => [],
            'name'        => [],
            'description' => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['XXX']
        ];
    }
}