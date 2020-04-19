<?php

namespace alina\mvc\model;

use alina\utils\Request;

class watch_fools extends _BaseAlinaModel
{
    public $table = 'watch_fools';

    public function fields()
    {
        $RQ = Request::obj();

        return [
            'id'         => [],
            'ip'         => [
                'default' => $RQ->IP,
            ],
            'browser'    => [
                'default' => $RQ->BROWSER,
            ],
            'method'     => [
                'default' => $RQ->METHOD,
            ],
            'header'     => [
                'default' => json_encode($RQ->HEADERS, JSON_UNESCAPED_UNICODE),
            ],
            'get'        => [
                'default' => $RQ->QUERY_STRING,
            ],
            'post'       => [
                'default' => json_encode($RQ->POST, JSON_UNESCAPED_UNICODE),
            ],
            'file'       => [
                'default' => json_encode($RQ->FILES, JSON_UNESCAPED_UNICODE),
            ],
            'created_at' => [
                'default' => ALINA_TIME,
            ],
        ];
    }
}
