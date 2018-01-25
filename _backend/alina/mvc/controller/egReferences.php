<?php

namespace alina\mvc\controller;

use alina\mvc\model\referenceProcessor;
use alina\mvc\model\user;

class egReferences
{
    public function actionIndex()
    {
        $m          = new user();
        $conditions = ["{$m->alias}.id" => '2',];
        $orderArray = [["{$m->alias}.id", 'DESC']];
        $limit      = 2;
        $offset     = 2;

        $m          = new user();
        $conditions = [
            [function ($qu) {
                $qu->whereIn('user.id', [2, 3]);
            }],
            'firstname' => 'Третий'
        ];
        $orderArray = [["{$m->alias}.id", 'DESC']];
        $limit      = NULL;
        $offset     = NULL;

        throw new \ErrorException('Hellow, World!');

        $m->getAllWithReferences($conditions, $orderArray, $limit, $offset);

        echo '<pre>';
        print_r($m->collection->toArray());
        echo '</pre>';
    }
}