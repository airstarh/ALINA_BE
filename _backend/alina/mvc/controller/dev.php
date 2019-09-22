<?php

namespace alina\mvc\controller;

class dev
{
    private $get = [
        'index' => 'val',
        'user'  => [
            'name' => 'Alina',
            'age'  => '21',
        ],
    ];

    /**
     * @route /dev/index
     */
    public function actionIndex(...$args)
    {


        return;
    }

    ##############################################

    /**
     * @route /dev/Info
     * @route /dev/Info/hello/world
     */
    public function actionInfo(...$args)
    {
        echo '<pre>';
        echo PHP_EOL;
        echo '$_GET';
        echo PHP_EOL;
        print_r($_GET);
        echo PHP_EOL;

        echo PHP_EOL;
        echo '$_POST';
        echo PHP_EOL;
        print_r($_POST);
        echo PHP_EOL;

        echo PHP_EOL;
        echo '$_POST resolvePostDataAsObject';
        echo PHP_EOL;
        print_r(\alina\utils\Sys::resolvePostDataAsObject());
        echo PHP_EOL;

        echo PHP_EOL;
        echo 'apache_request_headers';
        echo PHP_EOL;
        print_r(apache_request_headers());
        echo PHP_EOL;
        echo '</pre>';
    }
}
