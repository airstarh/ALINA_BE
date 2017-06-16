<?php

namespace alina\mvc\controller;


class dev
{
    public function actionTest()
    {
        echo 'Hello world';

        echo '<pre>';
        print_r($_GET);
        echo '</pre>';

        echo '<pre>';
        print_r($_SERVER);
        echo '</pre>';

        return;
    }
}