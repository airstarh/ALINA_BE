<?php

namespace alina\mvc\model;

use alina\utils\Data;

class tale extends _BaseAlinaModel
{
    public $table = 'tale';

    public function fields()
    {
        return [
            'id'           => [],
            'parent_id'    => [],
            'owner_id'     => [
                'default' => CurrentUser::obj()->id,
            ],
            'header'       => [],
            'body'         => [],
            'created_at'   => [
                'default' => ALINA_TIME,
            ],
            'modified_at'  => [
                'default' => ALINA_TIME,
            ],
            'publish_at'   => [
                'default' => ALINA_TIME,
            ],
            'is_submitted' => [
                'default' => 0,
            ],
        ];
    }

    public function uniqueKeys()
    {
        return [];
    }
}
