<?php

namespace alina\mvc\model;

use alina\utils\Data;

class login extends _BaseAlinaModel
{
    public $table = 'login';

    public function fields()
    {
        return [
            'id'          => [],
            'user_id'     => [],
            'token'       => [],
            'ip'          => [],
            'browser_enc' => [],
            'lastentered' => [
                'default' => ALINA_TIME,
            ],
            'expires_at'  => [
                'default' => ALINA_AUTH_EXPIRES,
            ],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['user_id', 'token'],
        ];
    }
}
