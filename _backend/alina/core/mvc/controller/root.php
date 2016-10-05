<?php

namespace alina\core\mvc\controller;


class root
{
    public function actionIndex(){
        echo '<pre>';
        print_r('Helow from Alina');
        echo '</pre>';
    }

    public function action404(){
        echo '<pre>';
        print_r('404 Page not found.');
        echo '</pre>';
    }

    public function actionException(){
        echo '<pre>';
        print_r('System Exception occurred');
        echo '</pre>';
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';
    }
}