<?php

namespace alina\mvc\controller;

use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;

class Auth
{
    /**
     * @route /Generic/index
     */
    public function actionLogin()
    {
    }

    public function actionRegister()
    {
        $vd = (object)[
            'email'       => '',
            'pass'        => '',
            'passConfirm' => '',
        ];
        $p  = \alina\utils\Data::deleteEmptyProps(\alina\utils\Sys::resolvePostDataAsObject());
        $vd = \alina\utils\Data::mergeObjects($vd, $p);
        ##################################################
        $m = new user($vd);
        ##################################################
        echo (new htmlAlias)->page($vd);
    }

    public function actionProfile()
    {
    }

    public function actionLogout()
    {
    }
}
