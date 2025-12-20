<?php

namespace alina\mvc\Model;

use alina\App;
use alina\Utils\Request;

class watch_visit extends _BaseAlinaModel
{
    public $table = 'watch_visit';

    public function fields()
    {
        #####
        $Request = Request::obj();

        #####
        return [
            'id'           => [],
            'ip'           => [
                'default' => $Request->IP,
            ],
            'browser_enc'  => [
                'default' => $Request->BROWSER_enc,
            ],
            'query_string' => [
                'default' => $Request->QUERY_STRING,
            ],
            'user_id'      => [
                'default' => CurrentUser::obj()->id(),
            ],
            'visited_at'   => [
                'default' => ALINA_TIME,
            ],
            'method'       => [
                'default' => $Request->METHOD,
            ],
            'data'         => [
                'default' => json_encode($Request, JSON_UNESCAPED_UNICODE),
            ],
            'controller'   => [
                'default' => Alina()->router->controller,
            ],
            'action'       => [
                'default' => Alina()->router->action,
            ],
            'suspicious'   => [
                'default' => 0,
            ],
            'ajax'         => [
                'default' => $Request->AJAX,
            ],
        ];
    }
}
