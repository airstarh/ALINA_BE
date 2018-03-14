<?php

namespace alina\mvc\view;

use alina\GlobalRequestStorage;

class json
{
    public function __construct($data = NULL)
    {

    }

    public function standardRestApiResponse($data = NULL, $toReturn = FALSE)
    {

        $this->setCrossDomainHeaders();

        $response             = [];
        $response['data']     = $data;
        $response['messages'] = \alina\message::returnAllMessages();
        $response['meta'] = GlobalRequestStorage::getAll();


        //ToDo: PROD! Security!
        $response['test'] = ['Проверка русских букв.',];
        $response['sys']  = $this->systemData();

        //Output.
        if ($toReturn) {
            return $response;
        }

        header('Content-Type: application/json; charset=utf-8');
        //ToDo: Think about encoding (utf8ize).
        echo json_encode(utf8ize($response));
        //echo json_encode($response, JSON_UNESCAPED_UNICODE);
        //echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        return TRUE;
    }

    public function simpleRestApiResponse($data = NULL, $toReturn = FALSE)
    {

        $this->setCrossDomainHeaders();

        $response = $data;

        //Output.
        if ($toReturn) {
            return $response;
        }

        header('Content-Type: application/json; charset=utf-8');
        //ToDo: Think about encoding (utf8ize).
        echo json_encode(utf8ize($response));
        //echo json_encode($response, JSON_UNESCAPED_UNICODE);
        //echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        return TRUE;
    }

    public function setCrossDomainHeaders()
    {
        setCrossDomainHeaders();

        return $this;
    }

    //ToDo: Never use on prod.
    public function systemData()
    {

        if (ALINA_MODE === 'PROD') {
            return [];
        }

        $sysData            = [];
        $sysData['method']  = $_SERVER['REQUEST_METHOD'];
        $sysData['time']    = microtime(TRUE) - ALINA_MICROTIME;
        $sysData['headers'] = getallheaders();
        $sysData['GET']     = $_GET;
        $sysData['POST']    = resolvePostDataAsObject();
        $sysData['FILE']    = $_FILES;
        $sysData['COOKIES'] = $_COOKIE;
        $sysData['SERVER']  = $_SERVER;
        isset($_SESSION) ? $sysData['SESSION'] = $_SESSION : NULL;

        return $sysData;
    }
}