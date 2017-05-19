<?php

namespace alina\mvc\controller;


class exampleHtmlPage
{
    public function actionIndex()
    {
        $data = 'Hello, World!';
        echo (new \alina\mvc\view\html)->page($data);
    }

}