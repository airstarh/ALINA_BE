<?php

namespace alina\mvc\controller;

use alina\exceptionValidation;
use alina\Mailer;
use alina\Message;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Obj;
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
            'form_id'  => __FUNCTION__,
            'mail'     => '',
            'password' => '',
            'uid'      => '',
            'token'    => '',
        ];
        ##################################################
        $p  = Data::deleteEmptyProps(Request::obj()->POST);
        $vd = Data::mergeObjects($vd, $p);
        if (empty($p->mail) || empty($p->password)) {
            echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

            return $this;
        }
        ##################################################
        $CU    = CurrentUser::obj();
        $LogIn = $CU->LogInByPass($vd->mail, $vd->password);
        if ($LogIn) {
            $user = $CU->name();
            Message::set("Welcome, {$user}!");
            Sys::redirect('/auth/profile', 303);
        } else {
            $CU->messages();
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
            'form_id'          => __FUNCTION__,
            'mail'             => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        ##################################################
        if (Request::isPost()) {
            $p  = Data::deleteEmptyProps(Request::obj()->POST);
            $vd = Data::mergeObjects($vd, $p);
            try {
                if ($vd->password !== $vd->confirm_password) {
                    throw new exceptionValidation('Passwords do not match');
                }
                $u = CurrentUser::obj();
                if ($u->Register($vd)) {
                    $u->messages();
                }
            } catch (exceptionValidation $e) {
                Message::set($e->getMessage(), [], 'alert alert-danger');
            }
        }
        ##################################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

        return $this;
    }

    ##################################################
    public function actionProfile($id = NULL)
    {
        if (empty($id)) {
            $id = CurrentUser::obj()->id;
        }
        if (empty($id)) {
            Sys::redirect('/auth/login', 307);
        }
        #####
        $vd = (object)[
            'user'    => (object)[],
            'sources' => (object)[],
        ];
        $u  = new user();
        #####
        if (Request::isPost($post)) {
            $u->updateById($post);
        }
        #####
        $u->getOneWithReferences(['user.id' => $id,]);
        #####
        unset($u->attributes->password);
        #####
        $vd->user    = $u->attributes;
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

    public function actionResetPasswordRequest()
    {
        $vd = (object)[
            'form_id' => __FUNCTION__,
            'message' => '',
            'mail'    => '',
        ];
        ##################################################
        if (Request::isPost($post)) {
            $vd = Data::mergeObjects($vd, $post);
            if (!empty($vd->mail)) {
                $mUser = new user();
                $atrs  = $mUser->getOne(['mail' => $vd->mail,]);
                if ($mUser->id) {
                    if ($atrs->reset_required != 1) {
                        $code = ALINA_TIME;
                        $mUser->updateById([
                            'reset_code'     => $code,
                            'reset_required' => 1,
                        ]);
                        (new Mailer())->sendVerificationCode($vd->mail, $code);
                        Sys::redirect('/auth/ResetPasswordWithCode', 307);
                    } else {
                        Message::set('Code was sent earlier', [], 'alert alert-danger');
                        Sys::redirect('/auth/ResetPasswordWithCode', 307);
                    }
                }
            }
        }
        ##################################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');

        return $this;
    }

    ##################################################

    public function actionResetPasswordWithCode()
    {
        $vd = (object)[
            'form_id'          => __FUNCTION__,
            'route_plan_b'     => '/auth/ResetPasswordWithCode',
            'reset_code'       => '',
            'mail'             => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        ##################################################
        if (Request::isPost($post)) {
            $vd = Data::mergeObjects($vd, $post);
            if (!empty($vd->mail) && !empty($vd->reset_code)) {
                $mUser = new user();
                $atrs  = $mUser->getOne(['mail' => $vd->mail,]);
                if ($mUser->id && $atrs->reset_required == 1) {
                    $vd->reset_code = trim($vd->reset_code);
                    if ($vd->reset_code === $atrs->reset_code) {
                        if ($vd->password === $vd->confirm_password) {
                            $mUser->updateById([
                                'password'       => $vd->password,
                                'reset_code'     => 0,
                                'reset_required' => 0,
                            ]);
                            Sys::redirect('/auth/login', 307);
                        } else {
                            Message::set('Passwords do not match', [], 'alert alert-danger');
                        }
                    } else {
                        Message::set('Reset code is incorrect.', [], 'alert alert-danger');
                    }
                }

            }
        }
        ##################################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }
}
