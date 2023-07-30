<?php

namespace osspb\mvc\controller;

use alina\Message;
use alina\mvc\view\html;

class main
{
    public function actionIndex()
    {
        //Message::setWarning(AlinaCfg('title'));
        $content = AlinaCfg('title');
        echo (new html)->page($content, html::$htmLayoutCleanBody);
    }

    public function action404()
    {
        Message::setDanger('Такой страницы нет на сайте');
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new html)->page();
        exit;
    }
}
