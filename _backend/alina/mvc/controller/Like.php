<?php

namespace alina\mvc\controller;

use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\like as mLike;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Request as Request;

class Like
{
    public $model;

    public function __construct()
    {
        $this->model = new mLike();
    }

    #####
    public function actionProcess()
    {
        AlinaRejectIfNotLoggedIn();
        if (Request::isPostPutDelete($post)) {
            $ref_table = $post->ref_table;
            $ref_id    = $post->ref_id;
            $user_id   = CurrentUser::obj()->id;
            $val       = $post->val;
            $attrs     = $this->model->getOne([
                'ref_table' => $ref_table,
                'ref_id'    => $ref_id,
                'user_id'   => $user_id,
            ]);
            if (isset($attrs->id) && !empty($attrs->id)) {
                $this->model->deleteById($attrs->id);
                $CurrentUserLiked = 0;
            } else {
                $this->model->insert($post);
                $CurrentUserLiked = 1;
            }
            $AmountLikes = (new mLike())
                ->q()
                ->where([
                    'ref_table' => $ref_table,
                    'ref_id'    => $ref_id,
                    'val'       => $val,
                ])
                ->count();
            $vd          = (object)[
                'CurrentUserLiked' => $CurrentUserLiked,
                'AmountLikes'      => $AmountLikes,
            ];
            echo (new htmlAlias)->page($vd);
        }
    }
    #####
    #region SELECT
    public function actionSelectList($pageSze = 10, $page = 1, $ref_table = 'tale', $ref_id = NULL)
    {
        $backendSortArray = [['lk.created_at', 'DESC']];
        $conditions       = ['lk.ref_table' => $ref_table, 'lk.ref_id' => $ref_id];
        $q                = $this->model->getAllWithReferencesPart1($conditions);
        $collection       = $this->model->getAllWithReferencesPart2($backendSortArray, $pageSze, $page);
        echo (new htmlAlias)->page($collection);
    }

    public function actionSelectById(...$params)
    {
        $id           = $params[0];
        $conditions[] = [$this->model->pkName, '=', $id];
        $attrs        = $this->model->getOneWithReferences($conditions);
        echo (new htmlAlias)->page($attrs);
    }

    #endregion SELECT
    #####
    #region INSERT
    public function actionInsert(...$params)
    {
        $data = (object)[];
        if (Request::isPostPutDelete($post)) {
            $data = $this->model->insert($post);
        }
        echo (new htmlAlias)->page($data);
    }
    #endregion INSERT
    #####
    #region UPDATE
    public function actionUpdate(...$params)
    {
        $data       = (object)[];
        $conditions = [];
        if (Request::isPostPutDelete($post)) {
            $data       = (object)$post['data'];
            $conditions = (array)$post['conditions'];
            $data       = $this->model->update($data, $conditions);
        }
        echo (new htmlAlias)->page($data);
    }

    #endregion UPDATE
    #####
    #region DELETE
    public function actionDelete(...$params)
    {
        $affectedRows = 0;
        if (Request::isPostPutDelete($post)) {
            $conditions   = (array)$post;
            $affectedRows = $this->model->delete($conditions);
        }
        echo (new htmlAlias)->page($affectedRows);
    }

    #endregion DELETE
    #####
}
