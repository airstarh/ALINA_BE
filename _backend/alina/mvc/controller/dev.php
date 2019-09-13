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
        $res          = http_build_query($this->get);
        $uri          = 'https://yandex.ru/search/?lr=193&text=%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82%20%D0%BC%D0%B8%D1%80';
        $uriParsed    = parse_url($uri);
        $uriParsedGet = $uriParsed['query'];
        $a            = [];
        parse_str($uriParsedGet, $a);
        $unparsed = unparse_url($uriParsed);
        echo '<pre>';
        print_r($unparsed);
        echo '</pre>';

        //echo 'YoYoYo!!!';

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
        echo 'apache_request_headers';
        echo PHP_EOL;
        print_r(apache_request_headers());
        echo PHP_EOL;
        echo '</pre>';
    }
}
