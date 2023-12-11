<?php

namespace zero\mvc\Controller;

use alina\Message;
use zero\CustomZeroFolder\CustomZeroClass;

class Main
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function action404()
    {
        Message::setDanger('<h1>404</h1>Такой страницы нет на сайте');
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new \alina\mvc\View\html)->page();
        exit;
    }


    public function actionTest()
    {
        Message::setInfo('Информационное сообщение');
        Message::setSuccess('Успешное сообщение');
        AlinaResponseSuccess(1);
        http_response_code(200);

        $vd = [
            'Простой' => 'текст',
            'func_get_args' => func_get_args(),
            'CustomZeroClass::someMethod()' => CustomZeroClass::someMethod(),
        ];

        echo (new \alina\mvc\View\html)->page($vd);
    }


}
