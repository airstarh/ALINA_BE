<?php

namespace alina\mvc\Model;

use alina\Utils\Request;

class watch_banned_visit extends _BaseAlinaModel
{
    public $table = 'watch_banned_visit';

    public function fields()
    {
        #####
        return [
            'id' => [],
            'ip' => [
                'default' => Request::obj()->IP,
            ],
            'user_id' => [
                'default' => CurrentUser::obj()->id() ?? null,
            ],
            'browser_enc' => [
                'default' => Request::obj()->BROWSER_enc,
            ],
            'reason' => [
                'default' => 'spam',
            ],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['ip', 'browser_enc', 'user_id'],
        ];
    }
}
