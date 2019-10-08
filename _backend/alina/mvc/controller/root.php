<?php

namespace alina\mvc\controller;


class root
{
    public function actionIndex()
    {
        echo (new \alina\mvc\view\html)->page();
    }

    public function action404()
    {
        http_response_code(404);
        echo (new \alina\mvc\view\html)->page();
    }

    public function actionException()
    {
        if (\alina\utils\Sys::isAjax()) {
            echo \alina\message::returnAllMessages();

            return TRUE;
        }

        echo (new \alina\mvc\view\html)->page();

        return TRUE;
    }
}
