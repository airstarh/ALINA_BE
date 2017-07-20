<?php

namespace alina\mvc\controller;


class alinaFileProxy
{
    public function actionIndex()
    {
        echo '<pre>';
        print_r('func_get_args');
        echo '</pre>';
        echo '<pre>';
        print_r(func_get_args());
        echo '</pre>';

        echo '<pre>';
        print_r('\alina\app::get()');
        echo '</pre>';
        echo '<pre>';
        print_r(\alina\app::get());
        echo '</pre>';


        echo '<pre>';
        print_r('$_GET');
        echo '</pre>';
        echo '<pre>';
        print_r($_GET);
        echo '</pre>';

        echo '<pre>';
        print_r('$_SERVER');
        echo '</pre>';
        echo '<pre>';
        print_r($_SERVER);
        echo '</pre>';
    }

    public function actionTestIt() {
        $p = 'alinaFileProxy/fullHtmlLayout.php';
        echo (new \alina\mvc\view\html)->piece($p);
    }
}