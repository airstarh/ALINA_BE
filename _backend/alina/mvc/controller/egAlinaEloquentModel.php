<?php

namespace alina\mvc\controller;

use \alina\mvc\model\eloquent\user;

class egAlinaEloquentModel
{
    public function actionIndex()
    {
        $m = new user();
        //$r = $m->all();
        $r = $m->where('firstname', 'LIKE', '%п%')->first();

        //$r = $r->toArray();
        echo '<pre>';
        //print_r($r->firstname);
        print_r($r);
        echo '</pre>';
    }
}