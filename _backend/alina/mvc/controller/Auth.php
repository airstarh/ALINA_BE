<?php

namespace alina\mvc\controller;

use alina\AppExceptionValidation;
use alina\Mailer;
use alina\Message;
use alina\mvc\model\_baseAlinaEloquentTransaction;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\login;
use alina\mvc\model\notification;
use alina\mvc\model\rbac_user_role;
use alina\mvc\model\tale as taleAlias;
use alina\mvc\model\user;
use alina\mvc\model\watch_login;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Request;
use alina\utils\Sys;
use alina\Watcher;
use Illuminate\Database\Query\Builder as BuilderAlias;

class Auth
{
    /**
     * @route /Auth/Login
     */
    public function actionLogin()
    {
        ##################################################
        $path = \alina\utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/login'),
        );
        AlinaRedirectIfNotAjax($path, 303, TRUE);
        ##################################################
        $vd = (object)[
            'form_id'  => __FUNCTION__,
            'mail'     => '',
            'password' => '',
            'uid'      => '',
            'token'    => '',
        ];
        ##################################################
        if (Request::isPostPutDelete($p)) {
            $p  = Data::deleteEmptyProps($p);
            $vd = Data::mergeObjects($vd, $p);
            if (empty($vd->mail) || empty($vd->password)) {
                AlinaResponseSuccess(0);
                Message::setDanger('Incorrect data');
                echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutMiddled);
                exit;
            }
            ##################################################
            $amount = (new watch_login())->q()->where([
                'mail'        => $vd->mail,
                'ip'          => Request::obj()->IP,
                'browser_enc' => Request::obj()->BROWSER_enc,
            ])->first();
            //ToDo: hardcoded
            if ($amount && $amount->visits && $amount->visits >= 10) {
                Message::setDanger('ATTENTION');
                Watcher::obj()->banVisit(NULL, NULL, 'Too many login attempts');
            }
            ##################################################
            $CU    = CurrentUser::obj();
            $LogIn = $CU->LogInByPass($vd->mail, $vd->password);
            /**
             * SUCCESS
             */
            if ($LogIn) {
                AlinaResponseSuccess(1);
                $user = $CU->name();
                (new watch_login())->delete([
                    'mail'        => $vd->mail,
                    'ip'          => Request::obj()->IP,
                    'browser_enc' => Request::obj()->BROWSER_enc,
                ]);
                Message::setSuccess("Welcome, %s!", [$user]);
                Request::obj()->METHOD = 'GET';
                Alina()->mvcGo('auth', 'profile');
                exit;
                //Sys::r-redirect('/auth/profile', 303);
            }
            /**
             * FAIL
             */
            else {
                AlinaResponseSuccess(0);
                ##################################################
                (new watch_login())->upsertByUniqueFields([
                    'mail'        => $vd->mail,
                    'ip'          => Request::obj()->IP,
                    'browser_enc' => Request::obj()->BROWSER_enc,
                ]);
                ##################################################
                $CU->messages();
            }
        }
        ##################################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutMiddled);

        return $this;
    }

    /**
     * @route /Auth/Register
     */
    public function actionRegister()
    {
        ##################################################
        $path = \alina\utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/register'),
        );
        AlinaRedirectIfNotAjax($path, 303, TRUE);
        ##################################################
        $vd = (object)[
            'form_id'          => __FUNCTION__,
            'mail'             => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        $CU = CurrentUser::obj();
        ##################################################
        if (Request::isPost()) {
            $p  = Data::deleteEmptyProps(Request::obj()->POST);
            $vd = Data::mergeObjects($vd, $p);
            if ($vd->password !== $vd->confirm_password) {
                AlinaResponseSuccess(0);
                Message::setDanger('Passwords do not match');
            }
            if (AlinaIsResponseSuccess()) {
                if ($CU->Register($vd)) {
                    Message::setSuccess('Success');
                    $CU->messages();
                    Sys::redirect('/auth/login', 303);
                }
            }
        }
        ##################################################
        $CU->resetDiscoveredData();
        ##################################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutMiddled);

        return $this;
    }

    ##################################################
    public function actionProfile($id = NULL)
    {
        ##################################################
        $path = \alina\utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/profile'),
        );
        AlinaRedirectIfNotAjax($path, 303, TRUE);
        ##################################################
        if (empty($id)) {
            $id = CurrentUser::obj()->id;
        }
        if (empty($id)) {
            AlinaRejectIfNotLoggedIn();
        }
        #####
        $vd = (object)[
            'form_id' => __FUNCTION__,
            'user'    => (object)[],
            'sources' => (object)[],
        ];
        $u  = new user();
        #####
        if (Request::isPostPutDelete($post)) {
            $id = $post->id;
            ##################################################
            $path = \alina\utils\FS::buildPathFromBlocks(
                AlinaCfg('frontend/path'),
                AlinaCfg('frontend/profile'),
            );
            AlinaRedirectIfNotAjax($path, 303, TRUE);
            ##################################################
            if (AlinaAccessIfAdminOrModeratorOrOwner($post->id)) {
                Request::obj()->R->route_plan_b = '/auth/profile';
                $u->updateById($post);
                Message::setSuccess('Profile updated!');
            }
        }
        #####
        $u->getOneWithReferences(['user.id' => $id,]);
        #####
        Data::sanitizeOutputObj($u->attributes);
        #####
        $vd->user = $u->attributes;
        //$vd->sources = $u->getReferencesSources();
        echo (new htmlAlias)->page($vd);
    }

    public function actionLogout()
    {
        $vd       = (object)[];
        $vd->name = CurrentUser::obj()->name();
        CurrentUser::obj()->LogOut();
        Message::setSuccess('THanks for being with us!');
        Sys::redirect('/tale/feed', 303);
    }

    public function actionResetPasswordRequest()
    {
        ##################################################
        $path = \alina\utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/resetPasswordRequest'),
        );
        AlinaRedirectIfNotAjax($path, 303, TRUE);
        ##################################################
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
                    //if ($atrs->reset_required != 1) {
                    $code = ALINA_TIME;
                    (new Mailer())->sendVerificationCode($vd->mail, $code);
                    $mUser->updateById([
                        'reset_code'     => $code,
                        'reset_required' => 1,
                    ]);
                    //}
                    // else {
                    //     Message::setWarning('Code was sent earlier', []);
                    // }
                    Sys::redirect("/auth/ResetPasswordWithCode?mail={$vd->mail}", 303);
                }
            }
        }
        ##################################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutMiddled);

        return $this;
    }
    ##################################################
    ##################################################
    ##################################################
    public function actionResetPasswordWithCode()
    {
        ##################################################
        $path = \alina\utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/resetPasswordWithCode'),
        );
        AlinaRedirectIfNotAjax($path, 303, TRUE);
        ##################################################
        $rd = Request::obj()->R;
        $vd = (object)[
            'form_id'          => __FUNCTION__,
            'route_plan_b'     => "/auth/ResetPasswordWithCode",
            'reset_code'       => '',
            'mail'             => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        $vd = Data::mergeObjects($vd, $rd);
        ##################################################
        if (Request::isPost($post)) {
            $vd = Data::mergeObjects($vd, $post);
            if (!empty($vd->mail) && !empty($vd->reset_code)) {
                $mUser  = new user();
                $uAttrs = $mUser->getOne(['mail' => $vd->mail,]);
                if ($mUser->id && $uAttrs->reset_required == 1) {
                    $vd->reset_code = trim($vd->reset_code);
                    if ($vd->reset_code === $uAttrs->reset_code) {
                        if ($vd->password === $vd->confirm_password) {
                            $mUser->updateById([
                                'password'       => $vd->password,
                                'reset_code'     => NULL,
                                'reset_required' => 0,
                            ]);
                            Message::setInfo('Password is changed');
                            Sys::redirect('/auth/login', 307);
                        }
                        else {
                            Message::setDanger('Passwords do not match');
                        }
                    }
                    else {
                        Message::setDanger('Reset code is incorrect.');
                    }
                }
                else {
                    Message::setDanger('User with such email did not request password reset');
                }
            }
        }
        ##################################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutMiddled);
    }

    ##################################################
    public function actionChangePassword()
    {
        ##################################################
        $path = \alina\utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/changePassword'),
        );
        AlinaRedirectIfNotAjax('$path', 303, TRUE);
        ##################################################
        if (!AlinaAccessIfLoggedIn()) {
            Message::setDanger('Login first');
            Sys::redirect('/auth/login', 303);
        }
        #####
        $vd = (object)[
            'password'         => '',
            'confirm_password' => '',
            'form_id'          => __FUNCTION__,
            'route_plan_b'     => '/auth/ChangePassword',
        ];
        if (Request::isPost($post)) {
            $vd = Data::mergeObjects($vd, $post);
            #####
            Data::validateObject($vd, [
                'password' => [
                    [
                        'f'   => $vd->password === $vd->confirm_password,
                        'msg' => 'Passwords do not match!',
                    ],
                ],
            ]);
            #####
            $m = new user();
            $m->updateById($vd, CurrentUser::obj()->id);
            if ($m->state_AFFECTED_ROWS === 1) {
                Message::setSuccess('Password is changed');
                Sys::redirect('/auth/profile', 303);
            }
            else if ($m->state_AFFECTED_ROWS > 1) {
                Message::setDanger('Something bad happened');
            }
            else {
                Message::setDanger('Password not changed!');
            }
        }
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutMiddled);
    }

    ##################################################
    public function actionUserDelete($id)
    {
        if (in_array($id, ['null', '', 'NULL', 0])) {
            $id = NULL;
        }
        $vd     = (object)[
            'form_id' => __FUNCTION__,
        ];
        $isPost = Request::isPostPutDelete($post);
        ##################################################
        if ($isPost && AlinaAccessIfAdminOrModeratorOrOwner($id) && $post->id == $id) {
            $vd = (new user())->bizDelete($id);
        }
        if ($vd && $vd->users == 1) {
            Message::setSuccess('Deleted');
        }
        else {
            AlinaResponseSuccess(0);
            Message::setDanger('Failed');
        }
        ########################################
        echo (new htmlAlias)->page($vd);

        return $this;
    }
    ##################################################
}
