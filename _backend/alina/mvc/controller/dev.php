<?php

namespace alina\mvc\controller;


class dev
{
    public function actionTest()
    {
        echo 'Hello world';

        //throw new \ErrorException('Test catcher');

        echo '<pre>';
        print_r(['$_GET[12]' => $_GET[12],]);
        //print_r('Hello');
        echo '</pre>';

        return;
    }
}