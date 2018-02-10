<?php

namespace alina\mvc\model;

class tag extends _BaseAlinaModel
{
    public $table = 'tag';

    public function fields()
    {
        return [
            'id'  => [],
            'name'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['name']
        ];
    }
}