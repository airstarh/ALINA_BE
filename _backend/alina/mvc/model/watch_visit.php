<?php

namespace alina\mvc\model;

use alina\app;
use alina\utils\Request;

class watch_visit extends _BaseAlinaModel
{
    public $table = 'watch_visit';

    public function fields()
    {
        #####
        $req = Request::obj();
        #####
        return [
            'id'           => [],
            'ip_id'        => [],
            'browser_id'   => [],
            'url_path_id'  => [],
            'query_string' => [
                'default' => $req->QUERY_STRING,
            ],
            'user_id'      => [
                'default' => CurrentUser::obj()->id,
            ],
            'cookie_key'   => [
                'default' => ALINA_TIME,
            ],
            'visited_at'   => [
                'default' => ALINA_TIME,
            ],
            'method'       => [
                'default' => $req->METHOD,
            ],
            'data'         => [
                'default' => var_export(['POST' => $req->POST, 'FILES' => $req->FILES,], 1),
            ],
            'controller'   => [
                'default' => Alina()->router->controller,
            ],
            'action'       => [
                'default' => Alina()->router->action,
            ],
            'ajax'         => [
                'default' => $req->AJAX,
            ],
        ];
    }
}
