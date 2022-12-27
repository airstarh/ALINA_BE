<?php

namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\rbac_role;
use alina\mvc\model\rbac_user_role;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\traits\RequestProcessor;
use alina\utils\Data;
use alina\utils\Request;

class Admin
{
    use RequestProcessor;

    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    ##################################################
    # region User Related
    public function actionUsers($pageSze = 5, $page = 1)
    {
        $vd = (object)[];
        ########################################
        if (Request::isPost()) {
            $post = Data::deleteEmptyProps(Request::obj()->POST);
            switch ($post->action) {
                case 'set-roles':
                    $this->userSetRoles($post);
                    break;
                case 'update':
                    $this->userUpdate($post);
                    break;
                case 'delete':
                    $this->userDelete($post);
                    break;
            }
        }
        ########################################
        #region Users
        $model                          = new user();
        $conditions                     = [];
        $sort[]                         = ["user.id", 'ASC'];
        $processResponse                = $this->processGetModelList($model, $conditions, $sort, $pageSze, $page);
        $collection                     = $processResponse['collection'];
        $pagination                     = $processResponse['pagination'];
        $vd->pagination                 = $pagination;
        $vd->pagination->path           = "/admin/users";
        $vd->pagination->flagHrefAsPath = TRUE;
        $vd->users                      = $collection->toArray();
        $vd->users                      = array_filter($vd->users, ['\alina\utils\Data', 'sanitizeOutputObj']);
        #endregion Users
        ########################################
        #egion Roles
        $mRoles    = new rbac_role();
        $vd->roles = $mRoles->getAllWithReferences()->toArray();
        #endregion Roles
        ########################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
    }

    private function userSetRoles($post)
    {
        $uid = $post->id;
        $m   = new rbac_user_role();
        $m->delete(['user_id' => $uid]);
        if (isset($post->role_ids) && Data::isIterable($post->role_ids)) {
            foreach ($post->role_ids as $rid) {
                $m->upsertByUniqueFields(['user_id' => $uid, 'role_id' => $rid]);
            }
        }
    }

    private function userUpdate($post)
    {
        $m = new user();
        $m->updateById($post);
    }

    private function userDelete($post)
    {
        $id = $post->id;
        $vd = (new user())->bizDelete($id);
        if ($vd && $vd->users == 1) {
            Message::setSuccess('Deleted');
            Message::setSuccess("Users: {$vd->users}");
            Message::setSuccess("notifications: {$vd->notifications}");
            Message::setSuccess("likes: {$vd->likes}");
            Message::setSuccess("tales: {$vd->tales}");
            Message::setSuccess("rbac_roles: {$vd->rbac_roles}");
            Message::setSuccess("login: {$vd->login}");
        }
        else {
            AlinaResponseSuccess(0);
            Message::setDanger('Failed');
        }

        return $vd;
    }
    # region User Related
    ##################################################
}
