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
        switch (Request::obj()->METHOD) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                //ToDo...
                break;
            default:
                break;
        }

        #####
        return [
            'id'           => [],
            'ip_id'        => [],
            'browser_id'   => [],
            'url_path_id'  => [],
            'query_string' => [
                'default' => Request::obj()->QUERY_STRING,
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
                'default' => Request::obj()->METHOD,
            ],
            'data'         => [
                'default' => json_encode($req, JSON_UNESCAPED_UNICODE),
            ],
            'controller'   => [
                'default' => app::get()->router->controller,
            ],
            'action'       => [
                'default' => app::get()->router->action,
            ],
            'ajax'         => [
                'default' => Request::obj()->AJAX,
            ],
        ];
    }
}
