<?php

namespace alina\mvc\model;

class router_alias extends _BaseAlinaModel
{
    public $table = 'router_alias';

    public function fields()
    {
        return [
            'id'    => [],
            'alias' => [],
            'url'   => [],
        ];
    }
}
