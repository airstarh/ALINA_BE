<?php

namespace zero\core\mvc\controller;


class main
{
    public function actionIndex(){
        echo '<pre>';
        print_r('Default Zero main Page');
        echo '</pre>';
        echo '<pre>';
        print_r(func_get_args());
        echo '</pre>';
    }

    public function action404(){
        echo '<pre>';
        print_r('404 Page not found. Zero');
        echo '</pre>';
    }

}