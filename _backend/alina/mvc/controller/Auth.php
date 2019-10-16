<?php

namespace alina\mvc\controller;

use alina\exceptionValidation;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Sys;

class Auth
{
    /**
     * @route /Generic/index
     */
    public function actionLogin()
    {
    }

    /**
     * @route /Auth/Register
     */
    public function actionRegister()
    {
        $vd = (object)[
            'table'            => 'user',
            'mail'             => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        $p  = Data::deleteEmptyProps(Sys::resolvePostDataAsObject());
        $vd = Data::mergeObjects($vd, $p);
        if (empty((array)$p)) {
            echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

            return $this;
        }
        try {
            ##################################################
            if ($vd->password !== $vd->confirm_password) {
                throw new exceptionValidation('Passwords do not match');
            }
            $vd->password = md5($vd->password);
            ##################################################
            $m = new user();
            $m->insert($vd);
            $vd = Data::mergeObjects($vd, $m);
        } catch (\Exception $e) {

        }

        ##################################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    public function actionProfile()
    {
    }

    public function actionLogout()
    {
    }
}
