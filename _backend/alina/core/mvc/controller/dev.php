<?php

namespace alina\core\mvc\controller;


class dev
{
    public function actionTest()
    {
        echo '<pre>';
        print_r(['$_GET[12]' => $_GET[12],]);
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