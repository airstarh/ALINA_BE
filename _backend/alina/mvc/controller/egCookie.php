<?php

namespace alina\mvc\controller;

use alina\cookie;

class egCookie
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    /**
     * @route /egCookie/Test001
     */
    public function actionTest001()
    {
        //cookie::setPath('a', '000');
        cookie::setPath('a/b/c/a1', 111);
        cookie::setPath('a/b/c/a2', 222);
        cookie::setPath('a/b/c1/a1', 333);
        //cookie::deletePath('a/b/c');
        echo '<pre>';
        print_r($_COOKIE);
        echo '</pre>';
        echo '<pre>';
        print_r($_SERVER['HTTP_COOKIE']);
        echo '</pre>';
    }
}
