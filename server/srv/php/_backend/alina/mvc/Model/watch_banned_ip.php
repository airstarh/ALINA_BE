<?php

namespace alina\mvc\Model;
class watch_banned_ip extends _BaseAlinaModel
{
    public $table = 'watch_banned_ip';

    public function fields()
    {
        return [
            'id'     => [],
            'ip'     => [],
            'reason' => [
                'default' => 'spam',
            ],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['ip'],
        ];
    }
}
