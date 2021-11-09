<?php

namespace alina\mvc\controller;

use alina\mvc\model\rbac_role;
use alina\mvc\model\rbac_user_role;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Request;

class Admin
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    ##################################################
    public function actionUsers($pageSze = 5, $page = 1)
    {
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
        $vd = (object)[];
        ########################################
        #region Users
        $conditions = [];
        $sort[]     = ["user.id", 'ASC'];
        $collection = $this->processResponse($conditions, $sort, $pageSze, $page);
        $vd->users  = $collection->toArray();
        #endregion Users
        ########################################
        #egion Roles
        $mRoles    = new rbac_role();
        $vd->roles = $mRoles->getAllWithReferences()->toArray();
        #endregion Roles
        ########################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutWide.php');
    }

    ##################################################
    protected function processResponse($conditions = [], $sort = [], $pageSize = 5, $pageCurrentNumber = 1, $paginationVersa = FALSE)
    {
        $model      = new user();
        $q          = $model->getAllWithReferencesPart1($conditions);
        $collection = $model->getAllWithReferencesPart2($sort, $pageSize, $pageCurrentNumber, $paginationVersa);

        return $collection;
    }

    ##################################################
    private function userSetRoles($post)
    {
        $uid = $post->id;
        $m   = new rbac_user_role();
        $m->delete(['user_id' => $uid]);
        foreach ($post->role_ids as $rid) {
            $m->upsertByUniqueFields(['user_id' => $uid, 'role_id' => $rid]);
        }
    }

    private function userUpdate($post)
    {
        $m = new user();
        $m->updateById($post);
    }

    private function userDelete($post)
    {
        $m = new user();
        $m->deleteById($post->id);
    }
}
