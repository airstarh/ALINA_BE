<?php

namespace alina\mvc\controller;

use alina\mvc\model\referenceProcessor;
use alina\mvc\model\user;

class egReferences
{
    public function actionIndex()
    {
        $m = new user();
        $q = $m->q();
        $q->select(["{$m->alias}.*"]);
        $m->joinHasOne();
        $m->orderByArray([['id', 'ASC']]);
        $m->collection = $m->collection = $q->get();
        $m->joinHasMany();

        echo '<pre>';
        print_r($m->collection->toArray());
        echo '</pre>';
    }
}