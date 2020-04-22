<?php

namespace alina\mvc\model;

use alina\utils\Request;

class like extends _BaseAlinaModel
{
    public $table = 'lk';

    public function fields()
    {
        return [
            'id'         => [],
            'ref_table'  => [],
            'ref_id'     => [],
            'user_id'    => [
                'default' => CurrentUser::obj()->id,
            ],
            'val'        => [
                'default' => 1,
            ],
            'created_at' => [
                'default' => ALINA_TIME,
            ],
        ];
    }
}
