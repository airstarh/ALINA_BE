<?php

namespace alina\mvc\View;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\Model\CurrentUser;

class json
{
    public function standardRestApiResponse($data = null)
    {
        $response             = [];
        $response['data']     = $data;
        $response['messages'] = Message::returnAllMessages();

        if (AlinaAccessIfAdmin()) {
            $response['messages_admin'] = MessageAdmin::returnAllMessages();
        }
        $response['meta']        = GlobalRequestStorage::getAll();
        $response['CurrentUser'] = CurrentUser::obj()->attributes();

        return static::response($response);
    }

    public function simpleRestApiResponse($data = null)
    {
        return static::response($data);
    }

    public static function response($response)
    {
        header('Content-Type: application/json; charset=utf-8');

        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}
