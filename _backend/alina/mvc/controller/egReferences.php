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
        $users = $q->get();

        $hasMany = 'tag';
        $qr = $m->qRefHasMany($hasMany);
        $qr->whereIn('glue.entity_id', $users->pluck('id'));
        $qrData = $qr->get();

        foreach ($users as $oUser) {
            $oUser->{$hasMany} = [];
            foreach ($qrData as $oQrd) {
                if ($oUser->id === $oQrd->parent_id) {
                    $oUser->{$hasMany}[] = $oQrd;
                }
            }
        }


        echo '<pre>';
        print_r($users->toArray());
        echo '</pre>';
    }
}