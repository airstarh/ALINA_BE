<?php

namespace alina\mvc\controller;

use alina\exceptionValidation;
use alina\message;
use alina\mvc\model\_BaseAlinaModel;
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
            ##################################################
            $u = new user();
            $uData = $u->insert($vd);
            if (isset($u->id)) {
                $ur = new _BaseAlinaModel(['table' => 'rbac_user_role']);
                $ur->insert([
                    'user_id' => $u->id,
                    //TODo: Hardcoded, 5-servants
                    'role_id' => 5,
                ]);
                if (isset($ur->id)) {
                    message::set('Registration has passed successfully!');
                }
            }
        } catch (\Exception $e) {
            message::set($e->getMessage(), [], 'alert alert-danger');
        }

        ##################################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    public function actionProfile($id)
    {
        $vd = (object)[];
        $u = new user();
        $u->getAllWithReferences(['user.id' => $id,]);
        $vd->user = $u;
        echo (new htmlAlias)->page($vd);
    }

    public function actionLogout()
    {
    }
}
