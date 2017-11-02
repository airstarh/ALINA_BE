<?php
namespace alina\mvc\model\eloquent\eav;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;

class attr extends BaseEloquentModel
{
    protected $table = 'attr';

    public function uniqueKeys()
    {
        return [
            ['name_sys'],
        ];
    }

    public function fields()
    {
        return [
            'name_human' => [
                'filters' => [
                    'ucfirst'
                ],
            ],
        ];
    }

    protected $defaults = [
        'order'             => 1,
        'quantity'          => 1,
        'val_table' => 'value_varchar_500',
    ];
}