<?php

namespace alina\core\mvc\controller;


class dev
{
    public function actionTest()
    {
        \alina\core\cookie::setPath('a/b/c/a1', 111);
        \alina\core\cookie::setPath('a/b/c/a2', 222);
        \alina\core\cookie::setPath('a/b/c1/a1', 333);
        fDebug($_COOKIE);
        \alina\core\cookie::deletePath('a/b/c');


        echo '<pre>';
        print_r(getcookie());
        echo '</pre>';

        echo '<pre>';
        print_r($_COOKIE);
        echo '</pre>';

        echo '<pre>';
        print_r($_SERVER['HTTP_COOKIE']);
        echo '</pre>';
    }

}