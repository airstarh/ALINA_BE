<?php

namespace alina\mvc\model;

use alina\utils\Data;
use Illuminate\Database\Capsule\Manager as Dal;
use Illuminate\Database\Query\Builder as BuilderAlias;

class notification extends _BaseAlinaModel
{
    public $table = 'notification';

    public function fields()
    {
        return [
            'id'           => [],
            'to_id'        => [],
            'from_id'      => [],
            'txt'          => [],
            'params'       => [],
            'link'         => [],
            'severity_id'  => [],
            'is_shown'     => [
                'default' => 0,
            ],
            'created_at'   => [
                'default' => ALINA_TIME,
            ],
            'id_root'      => [],
            'id_answer'    => [],
            'id_highlight' => [],
            'tbl'          => [
                'default' => 'tale',
            ],
            'bind_tbl'     => [],
            'bind_id'      => [],
        ];
    }

    ##################################################
    public function referencesTo()
    {
        return [
            'to'       => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS to', 'to.id', '=', "{$this->alias}.to_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', [
                        'to.firstname AS to_firstname',
                        'to.lastname AS to_lastname',
                    ]],
                ],
            ],
            'from'     => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS from', 'from.id', '=', "{$this->alias}.from_id"],
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
            'severity' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'notification_severity AS severity', 'severity.id', '=', "{$this->alias}.severity_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', [
                        'severity.human_name AS severity_human_name',
                        'severity.class AS severity_class',
                    ]],
                ],
            ],
        ];
    }
    ##################################################
}
