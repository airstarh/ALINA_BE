<?php

namespace alina\mvc\controller;


class exampleCaseSensitivity
{
    public function actionIndex()
    {

    }

    public function actionCaseSensitive()
    {
        echo (new \alina\mvc\view\html)->page('');
    }

}