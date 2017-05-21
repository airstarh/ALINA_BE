<?php

namespace alina\mvc\controller;


class exampleNg
{
    public function actionIndex()
    {
        $p = 'exampleNg/fullHtmlLayout.php';
        echo (new \alina\mvc\view\html)->piece($p);
    }

}