<?php

namespace alina\mvc\Model;

use alina\App;
use alina\Utils\Request;

class watch_banned_visit extends _BaseAlinaModel
{
    public $table = 'watch_banned_visit';

    public function fields()
    {
        #####
        return [
            'id'          => [],
            'ip'          => [],
            'browser_enc' => [],
            'reason'      => [
                'default' => 'spam',
            ],
        ];
    }

    public
    function uniqueKeys()
    {
        return [
            ['ip', 'browser_enc'],
        ];
    }
}
