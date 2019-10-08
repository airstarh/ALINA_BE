<?php

namespace alina\mvc\controller;

use alina\exceptionCatcher;

class AdminTests
{
    /**
     * @route /AdminTests/Errors
     */
    public function actionErrors(...$args)
    {
        try {
            //$x = 10 / 0;
            throw new \ErrorException(11111111111111);
        } catch (\Exception $e) {
            //throw $e;
            exceptionCatcher::obj()->exception($e, FALSE);
            echo (new \alina\mvc\view\html)->page('1234');
        }

        return $this;
    }
    ##############################################
}
