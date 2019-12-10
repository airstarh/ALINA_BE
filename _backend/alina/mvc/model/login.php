<?php

namespace alina\mvc\model;

use alina\utils\Data;

class login extends _BaseAlinaModel
{
    public $table = 'login';

    public function fields()
    {
        return [
            'id'               => [],
            'user_id'          => [],
            'authtoken'        => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['user_id', 'watch_browser_id'],
        ];
    }
}
