<?php

namespace m45a\mvc\Controller;

use alina\Message;

class Main
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function action404()
    {
        Message::setDanger('Такой страницы нет на сайте');
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new \alina\mvc\View\html)->page();
        exit;
    }
}
