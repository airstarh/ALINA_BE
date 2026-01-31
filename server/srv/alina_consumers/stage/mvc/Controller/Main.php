<?php

namespace stage\mvc\Controller;

use alina\Message;
use stage\CustomZeroFolder\CustomZeroClass;
use alina\mvc\View\html;

class Main
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function action404()
    {
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new html)->page((object) [
            'pageNotFound' => ___('Page not found'),
        ]);
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
