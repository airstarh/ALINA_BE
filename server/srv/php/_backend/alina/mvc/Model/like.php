<?php

namespace alina\mvc\Model;

use alina\Utils\Request;

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

    #####
    public function referencesTo()
    {
        return [
            'from' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS from', 'from.id', '=', "{$this->alias}.user_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', [
                        'from.firstname AS from_firstname',
                        'from.lastname AS from_lastname',
                        'from.emblem AS from_emblem',
                    ]],
                ],
            ],
        ];
    }
    #####
}
