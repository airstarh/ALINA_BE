<?php

namespace alina\mvc\model;

class watch_visit extends _BaseAlinaModel
{
    public $table = 'watch_visit';

    public function fields()
    {
        return [
            'id'           => [],
            'ip_id'        => [],
            'browser_id'   => [],
            'url_path_id'  => [],
            'query_string' => [],
            'user_id'      => [
                'default' => CurrentUser::obj()->id
            ],
            'cookie_key'   => [
                'default' => ALINA_TIME,
            ],
            'visited_at'   => [
                'default' => ALINA_TIME,
            ],
        ];
    }
}
