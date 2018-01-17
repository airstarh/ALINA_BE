<?php

namespace alina\mvc\controller;


class egCaseSensitivity
{
    public function actionIndex()
    {

    }

    /**
     * URLs:
     * http://alinazero/egCaseSensitivity/TestCase/lalala?hello='world'
     */
    public function actionTestCase()
    {
        $content = func_get_args();
        echo (new \alina\mvc\view\html)->page($content);
    }

}