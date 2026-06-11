<?php

namespace alina\mvc\Model;

use alina\Utils\Data;

class notification_severity extends _BaseAlinaModel
{
    public $table = 'notification_severity';

    public function fields()
    {
        return [
            'id'         => [],
            'human_name' => [],
            'class'      => [],
        ];
    }
    ##################################################
}
