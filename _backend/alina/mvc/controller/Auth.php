<?php

namespace alina\mvc\controller;

use alina\exceptionValidation;
use alina\Message;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Request;
use alina\utils\Sys;

class Auth
{
    /**
     * @route /Auth/Login
     */
    public function actionLogin()
    {
        $vd = (object)[
            'mail'     => '',
            'password' => '',
        ];
        ##################################################
        $p  = Data::deleteEmptyProps(Request::obj()->POST);
        $vd = Data::mergeObjects($vd, $p);
        if (empty((array)$p)) {
            echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

            return $this;
        }
        ##################################################
        $CU    = CurrentUser::obj();
        $LogIn = $CU->LogInByPass($vd->mail, $vd->password);
        if ($LogIn) {
            $user = $CU->name();
            Message::set("Welcome, {$user}!");
        }
        foreach ($CU->msg as $i=>$v) {
            Message::set($v);
        }
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

        return $this;
    }

    /**
     * @route /Auth/Register
     */
    public function actionRegister()
    {
        ##################################################
        $vd = (object)[
            'mail'             => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        $p  = Data::deleteEmptyProps(Sys::resolvePostDataAsObject());
        $vd = Data::mergeObjects($vd, $p);
        ##################################################
        if (empty((array)$p)) {
            echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

            return $this;
        }
        ##################################################
        try {
            if ($vd->password !== $vd->confirm_password) {
                throw new exceptionValidation('Passwords do not match');
            }
            $u = CurrentUser::obj();
            $u->Register($vd);
        } catch (exceptionValidation $e) {
            Message::set($e->getMessage(), [], 'alert alert-danger');
        }
        ##################################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

        return $this;
    }

    public function actionProfile($id = NULL)
    {
        if (empty($id)) {
            $id = CurrentUser::obj()->id;
        }
        if (empty($id)) {
            Sys::redirect('/auth/login', 302);
        }
        $vd = (object)[];
        $u  = new user();
        $u->getAllWithReferences(['user.id' => $id,]);
        $vd->user    = $u;
        $vd->sources = $u->getReferencesSources();
        echo (new htmlAlias)->page($vd);
    }

    public function actionLogout()
    {
        $vd       = (object)[];
        $vd->name = CurrentUser::obj()->name();
        CurrentUser::obj()->LogOut();
        Sys::redirect('/Auth/Login', 307);
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }
}
