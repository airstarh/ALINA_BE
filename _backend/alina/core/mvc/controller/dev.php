<?php

namespace alina\core\mvc\controller;


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

        echo '<pre>';
        print_r(2/0);
        echo '</pre>';
    }


    public function actionTestCookie()
    {
        \alina\core\cookie::setPath('a/b/c/a1', 111);
        \alina\core\cookie::setPath('a/b/c/a2', 222);
        \alina\core\cookie::setPath('a/b/c1/a1', 333);
        \alina\core\cookie::deletePath('a/b/c');

        echo '<pre>';
        print_r($_COOKIE);
        echo '</pre>';

        echo '<pre>';
        print_r($_SERVER['HTTP_COOKIE']);
        echo '</pre>';
    }

}