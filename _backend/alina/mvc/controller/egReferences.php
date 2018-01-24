<?php

namespace alina\mvc\controller;

use alina\mvc\model\referenceProcessor;
use alina\mvc\model\user;

class egReferences
{
    public function actionIndex()
    {
        $m = new user();
        $q = $m->q('user');
        $q->select(['user.*']);
        $m->orderByArray([['id', 'ASC']]);
        (new referenceProcessor($m))->joinHasOne();
        $parentCollection = $m->collection = $q->get();
        $forIds = $parentCollection->pluck($m->pkName);

        $qArr = (new referenceProcessor($m))->joinHasMany();

        $qRefResult = [];
        foreach ($qArr as $rName=> $q) {
            $refs = $qRefResult[$rName] = $q->get();
            foreach ($parentCollection as $mParent) {
                foreach ($refs as $row) {
                    if ($mParent->{$m->pkName} === $row->main_id) {
                        $mParent->{$rName}[] = $row;
                    }
                }
            }


        }


        echo '<pre>';
        print_r($parentCollection->toArray());
        //print_r($qRefResult);
        echo '</pre>';
    }
}