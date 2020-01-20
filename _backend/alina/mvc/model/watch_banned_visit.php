<?php

namespace alina\mvc\model;

use alina\app;
use alina\utils\Request;

class watch_banned_visit extends _BaseAlinaModel
{
    public $table = 'watch_banned_visit';

    public function fields()
    {
        #####
        return [
            'id'         => [],
            'ip_id'      => [],
            'browser_id' => [],
        ];
    }

    public
    function uniqueKeys()
    {
        return [
            ['ip_id', 'browser_id'],
        ];
    }

}
