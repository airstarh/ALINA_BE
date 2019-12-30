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
        if (CurrentUser::obj()->isAdmin()) {
            $response['messages_admin'] = MessageAdmin::returnAllMessages();
        }
        $response['meta']        = GlobalRequestStorage::getAll();
        $response['CurrentUser'] = CurrentUser::obj()->attributes();
        //ToDo: PROD! Security!
        $response['test'] = ['Проверка русских букв.',];
        $response['sys']  = $this->systemData();

        //Output.
        if ($toReturn) {
            return $response;
        }

        header('Content-Type: application/json; charset=utf-8');

        //ToDo: Think about encoding (utf8ize).
        return json_encode(\alina\utils\Data::utf8ize($response));
        //echo json_encode($response, JSON_UNESCAPED_UNICODE);
        //echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        //return TRUE;
    }

    public function simpleRestApiResponse($data = NULL, $toReturn = FALSE)
    {
        $response = $data;

        //Output.
        if ($toReturn) {
            return $response;
        }

        header('Content-Type: application/json; charset=utf-8');
        //ToDo: Think about encoding (utf8ize).
        echo json_encode(\alina\utils\Data::utf8ize($response));
        //echo json_encode($response, JSON_UNESCAPED_UNICODE);
        //echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        return TRUE;
    }

    //ToDo: Security!!! Never use on prod.
    public function systemData()
    {

        if (ALINA_MODE === 'PROD') {
            return [];
        }

        $sysData = Sys::SUPER_DEBUG_INFO();

        return $sysData;
    }
}
