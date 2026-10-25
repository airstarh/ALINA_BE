<?php

namespace alina\mvc\Controller;

use alina\Mailer;
use alina\Message;
use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\file as fileModel;
use alina\mvc\Model\rbac_role;
use alina\mvc\Model\rbac_user_role;
use alina\mvc\Model\user;
use alina\mvc\Model\watch_login;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\Request;
use alina\Utils\Sys;
use alina\Watcher;

class Auth
{
    /**
     * @route /Auth/Login
     */
    public function actionLogin()
    {
        $CU = CurrentUser::obj();

        ##################################################
        if (AlinaAccessIfLoggedIn()) {
            Sys::redirect('/');
        }
        ##################################################
        $path = \alina\Utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/login'),
        );
        AlinaRedirectIfNotAjax($path, 303, true);
        ##################################################
        $vd = (object)[
            'form_id'  => __FUNCTION__,
            'mail'     => '',
            'password' => '',
            'uid'      => '',
            'token'    => '',
        ];

        ##################################################
        if (Request::obj()->isPostPutDelete($p)) {
            $p  = Data::deleteEmptyProps($p);
            $vd = Data::mergeObjects($vd, $p);

            if (empty($vd->mail) || empty($vd->password)) {
                AlinaResponseSuccess(0);
                Message::setDanger('Incorrect data');
                AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutMiddled));
            }
            ##################################################
            $LogIn        = $CU->LogInByPass($vd->mail, $vd->password);
            $vd->password = '';

            /**
             * SUCCESS
             */
            if ($LogIn) {
                AlinaResponseSuccess(1);
                (new watch_login())->delete([
                    'mail'        => $vd->mail,
                    'ip'          => Request::obj()->IP,
                    'browser_enc' => Request::obj()->BROWSER_enc,
                ]);
            }
            /**
             * FAIL
             */ #
            else {
                AlinaResponseSuccess(0);
                ##################################################
                (new watch_login())->upsertByUniqueFields([
                    'mail'        => $vd->mail,
                    'ip'          => Request::obj()->IP,
                    'browser_enc' => Request::obj()->BROWSER_enc,
                ]);
                Watcher::obj()->mVisitAddBanPoints(2);
                ##################################################
            }
        }
        ##################################################
        $CU->messages();
        AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutMiddled));
    }

    /**
     * @route /Auth/Register
     */
    public function actionRegister()
    {
        ##################################################
        $path = \alina\Utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/register'),
        );
        AlinaRedirectIfNotAjax($path, 303, true);
        ##################################################
        $vd = (object)[
            'form_id'          => __FUNCTION__,
            'mail'             => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        $CU = CurrentUser::obj();

        ##################################################
        if (Request::obj()->isPost()) {
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
        AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutMiddled));

        return $this;
    }

    ##################################################
    public function actionProfile($id = null)
    {
        ##################################################
        if (empty($id)) {
            $id = CurrentUser::obj()->id();
        }

        if (empty($id)) {
            AlinaRejectIfNotLoggedIn();
        }

        ##################################################
        if (! Request::obj()->AJAX) {
            $path = \alina\Utils\FS::buildPathFromBlocks(
                AlinaCfg('frontend/path'),
                AlinaCfg('frontend/profile'),
                $id
            );
            AlinaRedirectIfNotAjax($path, 303, true);
        }
        ##################################################
        $vd = (object)[
            'form_id' => __FUNCTION__,
            'user'    => (object)[],
            'sources' => (object)[],
        ];
        $u = new user();

        #####
        if (Request::obj()->isPostPutDelete($post)) {
            $id = $post->id;
            ##################################################
            $path = \alina\Utils\FS::buildPathFromBlocks(
                AlinaCfg('frontend/path'),
                AlinaCfg('frontend/profile'),
            );
            AlinaRedirectIfNotAjax($path, 303, true);

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
        AlinaEcho((new htmlAlias())->page($vd));
    }

    public function actionUsers($page = 1)
    {
        $page       = max(1, (int) $page);
        $pageSize   = 50;
        $search     = trim((string) (Request::obj()->GET->query ?? ''));
        $model      = new user();
        $query      = $model->getAllWithReferencesPart1([
            ['user.is_deleted', '!=', 1],
        ]);

        $query->select([
            'user.id',
            'user.firstname',
            'user.lastname',
            'user.emblem',
            'user.about_myself',
        ]);

        if ($search !== '') {
            $pattern = '%' . $search . '%';
            $query->where(static function ($query) use ($pattern) {
                $query
                    ->where('user.firstname', 'LIKE', $pattern)
                    ->orWhere('user.lastname', 'LIKE', $pattern)
                    ->orWhereRaw("CONCAT_WS(' ', user.firstname, user.lastname) LIKE ?", [$pattern]);
            });
        }

        $users = $model->getAllWithReferencesPart2([
            ['user.id', 'ASC'],
        ], $pageSize, $page);

        $publicUsers = $users->map(static function ($user) {
            return (object)[
                'id'           => $user->id,
                'firstname'    => $user->firstname,
                'lastname'     => $user->lastname,
                'emblem'       => $user->emblem,
                'about_myself' => $user->about_myself,
            ];
        })->values()->toArray();

        AlinaEcho((new htmlAlias())->page((object)[
            'users' => $publicUsers,
        ]));
    }

    public function actionProfileAdmin($id)
    {
        AlinaRejectIfNotAdminOrModerator();
        $id = (int) $id;
        if ($id < 1) {
            AlinaReject(null, 404);
        }

        if (Request::obj()->isPostPutDelete($post)) {
            $updates = [];
            foreach (['mail', 'firstname', 'lastname', 'birth', 'language', 'is_verified', 'banned_till', 'is_deleted'] as $field) {
                if (property_exists($post, $field)) {
                    $updates[$field] = $post->{$field};
                }
            }
            if (property_exists($post, 'password') && trim((string) $post->password) !== '') {
                $updates['password'] = $post->password;
            }
            if ($updates) {
                (new user())->updateById($updates, $id);
            }

            $userRole = new rbac_user_role();
            $userRole->delete(['user_id' => $id]);
            if (isset($post->role_ids) && Data::isIterable($post->role_ids)) {
                foreach (array_unique(array_map('intval', (array) $post->role_ids)) as $roleId) {
                    if ($roleId > 0) {
                        $userRole->upsertByUniqueFields(['user_id' => $id, 'role_id' => $roleId]);
                    }
                }
            }
        }

        $userModel = new user();
        $userData  = $userModel->getOneWithReferences(['user.id' => $id]);
        if (empty($userData->id)) {
            AlinaReject(null, 404);
        }

        $roleIds = array_map('intval', array_column((array) ($userData->rbac_user_role ?? []), 'id'));
        $roles   = (new rbac_role())->q()->orderBy('id', 'ASC')->get()->map(static function ($role) {
            return ['id' => (int) $role->id, 'name' => $role->name];
        })->values()->toArray();

        AlinaEcho((new htmlAlias())->page((object)[
            'user' => (object)[
                'id'          => (int) $userData->id,
                'mail'        => $userData->mail,
                'firstname'   => $userData->firstname,
                'lastname'    => $userData->lastname,
                'birth'       => $userData->birth,
                'language'    => $userData->language,
                'is_verified' => (int) $userData->is_verified,
                'banned_till' => $userData->banned_till,
                'is_deleted'  => (int) $userData->is_deleted,
                'created_at'  => $userData->created_at,
                'role_ids'    => $roleIds,
                'count_files' => (new fileModel())->q()->where('owner_id', '=', $id)->count(),
            ],
            'roles' => $roles,
        ]));
    }

    public function actionLogout()
    {
        $vd       = (object)[];
        $vd->name = CurrentUser::obj()->name();
        CurrentUser::obj()->LogOut();
        //Message::setSuccess('THanks for being with us!');
        Sys::redirect('/tale/feed', 303);
    }

    public function actionResetPasswordRequest()
    {
        ##################################################
        $path = \alina\Utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/resetPasswordRequest'),
        );
        AlinaRedirectIfNotAjax($path, 303, true);
        ##################################################
        $vd = (object)[
            'form_id' => __FUNCTION__,
            'message' => '',
            'mail'    => '',
        ];

        ##################################################
        if (Request::obj()->isPost($post)) {
            $vd = Data::mergeObjects($vd, $post);

            if (! empty($vd->mail)) {
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
        AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutMiddled));

        return $this;
    }

    ##################################################
    ##################################################
    ##################################################
    public function actionResetPasswordWithCode()
    {
        ##################################################
        $path = \alina\Utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/resetPasswordWithCode'),
        );
        AlinaRedirectIfNotAjax($path, 303, true);
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
        if (Request::obj()->isPost($post)) {
            $vd = Data::mergeObjects($vd, $post);

            if (! empty($vd->mail) && ! empty($vd->reset_code)) {
                $mUser  = new user();
                $uAttrs = $mUser->getOne(['mail' => $vd->mail,]);

                if ($mUser->id && $uAttrs->reset_required == 1) {
                    $vd->reset_code = trim($vd->reset_code);

                    if ($vd->reset_code === $uAttrs->reset_code) {
                        if ($vd->password === $vd->confirm_password) {
                            $mUser->updateById([
                                'password'       => $vd->password,
                                'reset_code'     => null,
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
        AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutMiddled));
    }

    ##################################################
    public function actionChangePassword()
    {
        ##################################################
        $path = \alina\Utils\FS::buildPathFromBlocks(
            AlinaCfg('frontend/path'),
            AlinaCfg('frontend/changePassword'),
        );
        AlinaRedirectIfNotAjax('$path', 303, true);

        ##################################################
        if (! AlinaAccessIfLoggedIn()) {
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

        if (Request::obj()->isPost($post)) {
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
            $m->updateById($vd, CurrentUser::obj()->id());

            if ($m->state_AFFECTED_ROWS === 1) {
                Message::setSuccess('Password is changed');
                Sys::redirect('/auth/profile', 303);
            }
            elseif ($m->state_AFFECTED_ROWS > 1) {
                Message::setDanger('Something bad happened');
            }
            else {
                Message::setDanger('Password not changed!');
            }
        }
        AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutMiddled));
    }

    ##################################################
    public function actionUserDelete($id)
    {
        if (in_array($id, ['null', '', 'NULL', 0])) {
            $id = null;
        }
        $vd = (object)[
            'form_id' => __FUNCTION__,
        ];
        $isPost = Request::obj()->isPostPutDelete($post);

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
        AlinaEcho((new htmlAlias())->page($vd));

        return $this;
    }
    ##################################################
}
