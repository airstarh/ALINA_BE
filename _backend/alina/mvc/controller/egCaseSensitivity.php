<?php

namespace alina\mvc\controller;


class egCaseSensitivity
{
    public function actionIndex()
    {

    }

    /**
     * URLs:
     * http://alinazero/exampleCaseSensitivity/actionCaseSensitive
     * http://alinazero/examplecasesensitivity/casesensitive
     */
    public function actionTestCase()
    {
        $content = func_get_args();
        echo (new \alina\mvc\view\html)->page($content);
    }

}