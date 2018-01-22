<?php

namespace alina\mvc\controller;


use alina\mvc\model\user;

class egReferences
{
    public function actionIndex()
    {
        $m = new user();
        $q = $m->q('user');
        $q->select(['*']);
        $m->qRefHasOne();
        $users = $q->get();

        $hasMany = 'role';
        $qr = $m->qRefHasMany($hasMany);
        $qr->whereIn('glue.user_id', $users->pluck('id'));
        $qrData = $qr->get();

        $usersArray = $users->toArray();
        foreach ($usersArray as $oUser) {
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