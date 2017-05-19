<?php

namespace alina\mvc\controller;


class exampleNg
{
    public function actionIndex()
    {
        $p = 'exampleNg/index.php';
        echo (new \alina\mvc\view\html)->piece($p);
    }

}