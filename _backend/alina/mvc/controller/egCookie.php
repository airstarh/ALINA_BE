<?php

namespace alina\mvc\controller;


class egCookie
{
    public function actionCookie()
    {
        \alina\cookie::setPath('a/b/c/a1', 111);
        \alina\cookie::setPath('a/b/c/a2', 222);
        \alina\cookie::setPath('a/b/c1/a1', 333);
        \alina\cookie::deletePath('a/b/c');

        echo '<pre>';
        print_r($_COOKIE);
        echo '</pre>';

        echo '<pre>';
        print_r($_SERVER['HTTP_COOKIE']);
        echo '</pre>';
    }

}