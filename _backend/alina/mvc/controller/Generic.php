<?php

namespace alina\mvc\controller;


class Generic
{
    public function actionIndex()
    {
        echo 'Hello';
        return $this;
    }
}
