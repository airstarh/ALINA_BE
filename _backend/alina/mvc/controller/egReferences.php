<?php

namespace alina\mvc\controller;

use alina\mvc\model\user;

class egReferences
{
    public function actionIndex()
    {
        $m = new user();
        $q = $m->q('user');
        $q->select(['user.*']);
        $m->qRefHasOne();
        $m->orderByArray([['id', 'ASC']]);
        $parentCollection = $m->collection = $q->get();

        $qRs = $m->qRefHasMany();

        foreach ($qRs as $rName => $qR) {

            $qrDbData = $qR->get();

            foreach ($parentCollection as $dbParent) {
                $dbParent->{$rName} = [];
                foreach ($qrDbData as $qrDbRow) {
                    if ($dbParent->{$m->pkName} === $qrDbRow->parent_id) {
                        $dbParent->{$rName}[] = $qrDbRow;
                    }
                }
            }
        }

        echo '<pre>';
        print_r($parentCollection->toArray());
        echo '</pre>';
    }
}