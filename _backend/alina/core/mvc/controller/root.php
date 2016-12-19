<?php

namespace alina\mvc\controller;


class root
{
    public function actionIndex()
    {
        echo '<pre>';
        print_r('Helow from Alina');
        echo '</pre>';
    }

    public function action404()
    {
        echo '<pre>';
        print_r('404 Page not found.');
        echo '</pre>';
    }

    public function actionException()
    {

        if (isAjax()) {
            echo json_encode(func_get_args());

            return TRUE;
        }

        echo '<pre>';
        print_r('<h1>System Exception occurred</h1>');
        echo '</pre>';

        print_r('<h2>Arguments</h2>');
        echo '<pre>';
        print_r(func_get_args());
        echo '</pre>';

        print_r('<h2>Message Collection</h2>');
        echo '<pre>';
        print_r([
                    '$flagCollectionInSession' => \alina\message::$flagCollectionInSession,
                    '$collection' => \alina\message::returnAllHtmlString(),
                ]);
        echo '</pre>';

        print_r('<h2>$_SESSION</h2>');
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';

        print_r('<h2>$_COOKIE</h2>');
        echo '<pre>';
        print_r($_COOKIE);
        echo '</pre>';

        print_r('<h2>$_SERVER</h2>');
        echo '<pre>';
        print_r($_SERVER);
        echo '</pre>';

        print_r('<h2>$_POST</h2>');
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';

        print_r('<h2>$_GET</h2>');
        echo '<pre>';
        print_r($_GET);
        echo '</pre>';
    }
}