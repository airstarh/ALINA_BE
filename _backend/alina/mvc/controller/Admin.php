<?php

namespace alina\mvc\controller;

use alina\App;
use alina\Message;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\model\modelNamesResolver;
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
    public function actionModels($model)
    {
        $vd    = (object)[];
        $model = modelNamesResolver::getModelObject($model);
        ########################################
        if (Request::isPost()) {
            $post = Data::deleteEmptyProps(Request::obj()->POST);
            switch ($post->action) {
                case 'update':
                    $model->updateById($post);
                    break;
                case 'delete':
                    $id = $model->{$model->pkName};
                    if (method_exists($model, 'bizDelete')) {
                        $model->bizDelete($id);
                    }
                    else {
                        $model->deleteById($id);
                    }
                    break;
            }
        }
        ########################################
        #region Models
        $model->state_APPLY_GET_PARAMS  = TRUE;
        $processResponse                = $this->processResponse($model);
        $collection                     = $processResponse['collection'];
        $pagination                     = $processResponse['pagination'];
        $vd->pagination                 = $pagination;
        $vd->pagination->path           = "/admin/models/{$model->table}";
        $vd->pagination->flagHrefAsPath = FALSE;
        $vd->model                      = $model;
        $vd->models                     = $collection->toArray();
        $vd->models                     = array_filter($vd->models, ['\alina\utils\Data', 'sanitizeOutputObj']);
        #endregion Models
        ########################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutWide.php');
    }

    ##################################################
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
        $processResponse                = $this->processResponse($model, $conditions, $sort, $pageSze, $page);
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
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutWide.php');
    }

    ##################################################

    /**
     * @param $model _BaseAlinaModel
     * @param $conditions
     * @param $sort
     * @param $pageSize
     * @param $pageCurrentNumber
     * @param $paginationVersa
     * @return array
     */
    protected function processResponse($model, $conditions = [], $sort = [], $pageSize = NULL, $pageCurrentNumber = NULL, $paginationVersa = FALSE)
    {
        $q          = $model->getAllWithReferencesPart1($conditions);
        $collection = $model->getAllWithReferencesPart2($sort, $pageSize, $pageCurrentNumber, $paginationVersa);
        $pagination = (object)[
            'pageCurrentNumber' => $model->pageCurrentNumber,
            'pageSize'          => $model->pageSize,
            'pagesTotal'        => $model->pagesTotal,
            'rowsTotal'         => $model->state_ROWS_TOTAL,
            'paginationVersa'   => $paginationVersa,
        ];

        return ['collection' => $collection, 'pagination' => $pagination];
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
}
