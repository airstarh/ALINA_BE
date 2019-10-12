<?php

namespace alina\mvc\controller;

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

        ];
        echo (new htmlAlias)->page($vd);
    }

    public function actionProfile()
    {
    }

    public function actionLogout()
    {
    }
}
