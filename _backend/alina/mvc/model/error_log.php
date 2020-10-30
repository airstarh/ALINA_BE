<?php

namespace alina\mvc\model;

use alina\App;
use alina\utils\Request;

class error_log extends _BaseAlinaModel
{
    public $table = 'error_log';

    public function fields()
    {
        #####
        return [
            'id'             => [],
            'ip'             => [
                'default' => Request::obj()->IP,
            ],
            'browser'        => [
                'default' => Request::obj()->BROWSER,
            ],
            'method'         => [
                'default' => Request::obj()->METHOD,
            ],
            'ajax'           => [
                'default' => Request::obj()->AJAX,
            ],
            'user_id'        => [
                'default' => CurrentUser::obj()->id,
            ],
            'url_path'       => [
                'default' => Request::obj()->URL_PATH,
            ],
            'query_string'   => [
                'default' => Request::obj()->QUERY_STRING,
            ],
            'request'        => [],
            'error_class'    => [],
            'error_severity' => [],
            'error_code'     => [],
            'error_text'     => [],
            'error_file'     => [],
            'error_line'     => [],
            'error_trace'    => [],
            'at'             => [
                'default' => ALINA_TIME,
            ],
        ];
    }
}
