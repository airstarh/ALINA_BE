<?php

namespace alina\mvc\controller;


class egHtmlPage
{
    public function actionIndex()
    {
        $data = 'Hello, World!';
        echo (new \alina\mvc\view\html)->page($data);
    }

}