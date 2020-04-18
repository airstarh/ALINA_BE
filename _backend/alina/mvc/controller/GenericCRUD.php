<?php

namespace alina\mvc\controller;

use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Request as Request;

class GenericCRUD
{
    public $model;

    public function __construct()
    {
        $this->model = new _BaseAlinaModel();
    }
    #####
    #region SELECT
    public function actionSelectList(...$params)
    {
        $conditions = (array)Request::obj()->GET;
        $q          = $this->model->getAllWithReferencesPart1($conditions);
        $collection = $this->model->getAllWithReferencesPart2();
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
