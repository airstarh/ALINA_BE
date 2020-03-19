<?php

namespace alina\mvc\controller;
class Generic
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    /**
     * @route /Generic/index
     * @route /Generic/index/test/path/parameters
     */
    public function actionIndex(...$arg)
    {
        echo 'Hello';
        echo '<pre>';
        print_r($arg);
        echo '</pre>';

        return $this;
    }
}
