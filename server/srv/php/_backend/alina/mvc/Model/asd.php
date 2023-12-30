<?php

namespace alina\mvc\Model;

class asd extends _BaseAlinaModel
{
    public $table = 'asd';

    public function fields()
    {
        return [
            'id'    => [],
            'price' => ['default' => 11.7,],
            'txt'   => ['default' => 'ASD'],
        ];
    }

    #####
}
