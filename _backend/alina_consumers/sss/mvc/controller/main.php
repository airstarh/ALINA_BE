<?php

namespace sss\mvc\controller;

use alina\Message;
use sss\CustomZeroFolder\CustomZeroClass;

class main
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function action404()
    {
        echo '<pre>';
        print_r('404 Page not found. Zero');
        echo '</pre>';
    }

    public function actionCheckAutoload()
    {
        $data = [
            func_get_args(),
            CustomZeroClass::someMethod(),
        ];
        echo (new \alina\mvc\view\html)->page($data);
    }
}
