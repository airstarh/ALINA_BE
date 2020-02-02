<?php

namespace alina\mvc\view;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\model\CurrentUser;
use alina\utils\Sys;

class json
{
    public function __construct($data = NULL)
    {

    }

    public function standardRestApiResponse($data = NULL, $toReturn = FALSE)
    {
        $response             = [];
        $response['data']     = $data;
        $response['messages'] = Message::returnAllMessages();
        if (AlinaAccessIfAdminOrNotProd()) {
            $response['messages_admin'] = MessageAdmin::returnAllMessages();
        }
        $response['meta']        = GlobalRequestStorage::getAll();
        $response['CurrentUser'] = CurrentUser::obj()->attributes();
        if (AlinaAccessIfAdminOrNotProd()) {
            $response['test'] = ['Проверка русских букв.',];
            $response['sys']  = $this->systemData();
        }
        //Output.
        header('Content-Type: application/json; charset=utf-8');

        return static::response($response);
    }

    public function simpleRestApiResponse($data = NULL, $toReturn = FALSE)
    {
        $response = $data;
        header('Content-Type: application/json; charset=utf-8');

        return static::response($response);
    }

    private function systemData()
    {
        return Sys::SUPER_DEBUG_INFO();
    }

    static public function response($response)
    {
        //ToDo: Think about encoding (utf8ize).
        //return json_encode($response);
        //return json_encode(\alina\utils\Data::utf8ize($response));
        return json_encode($response, JSON_UNESCAPED_UNICODE);
        //return json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
}
