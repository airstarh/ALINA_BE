<?php

namespace alina\mvc\controller;


class exampleAlinaSimpleModel
{
    public function actionIndex()
    {
        $m = new \alina\mvc\model\user();

        $r = $m->getAll();

        echo '<pre>';
        print_r($r);
        echo '</pre>';
    }
}