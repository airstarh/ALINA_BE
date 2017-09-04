<?php

namespace alina\mvc\controller;


class exampleAlinaEloquentModel
{
    public function actionIndex()
    {
        $m = new \alina\mvc\model\userEloquent();

        $r = $m->all();

        echo '<pre>';
        print_r($r->toArray());
        echo '</pre>';
    }
}