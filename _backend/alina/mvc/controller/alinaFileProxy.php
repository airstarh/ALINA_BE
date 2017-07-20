<?php

namespace alina\mvc\controller;


class alinaFileProxy
{
    public function actionIndex() {
        if (isset($_GET['file']) && !empty($_GET['file'])) {
            $relativePath = $_GET['file'];
            $relativePath = trim($relativePath, "'");
            $relativePath = trim($relativePath, '"');
            $p = \alina\app::get()->resolvePath($relativePath);
            giveFile($p);
        }
    }

    public function actionIndex_()
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