<?php

namespace alina\mvc\model;

use alina\app;
use alina\utils\Request;

class watch_visit extends _BaseAlinaModel
{
    public $table = 'watch_visit';

    public function fields()
    {

        switch (Request::obj()->METHOD) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $data = json_encode(Request::obj()->POST);
                break;
            default:
                $data = NULL;
                break;
        }

        return [
            'id'           => [],
            'ip_id'        => [],
            'browser_id'   => [],
            'url_path_id'  => [],
            'query_string' => [],
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
                'default' => $data,
            ],
            'controller'   => [
                'default' => app::get()->router->controller,
            ],
            'action'   => [
                'default' => app::get()->router->action,
            ],
        ];
    }
}
