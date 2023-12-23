<?php

namespace alina\mvc\Model;

class voc extends _BaseAlinaModel
{
    public $table = 'voc';

    public function fields()
    {
        return [
            'id'    => [],
            'from'  => [],
            'en_US' => [],
            'ru_RU' => [],
        ];
    }
}