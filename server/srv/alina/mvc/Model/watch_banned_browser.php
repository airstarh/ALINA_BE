<?php

namespace alina\mvc\Model;

class watch_banned_browser extends _BaseAlinaModel
{
    public $table = 'watch_banned_browser';

    public function fields()
    {
        return [
            'id'     => [],
            'enc'    => [],
            'reason' => [
                'default' => 'spam',
            ],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['enc'],
        ];
    }
}
