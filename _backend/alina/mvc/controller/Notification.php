<?php

namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\notification as notificationModel;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Request as Request;

class Notification
{
    public $model;

    public function __construct()
    {
        AlinaRejectIfNotLoggedIn();
        $this->model = new notificationModel();
    }

    public function actionSelectListLatest($pageSze = 5, $pageNumber = 1)
    {
        $conditions       = [
            'notification.to_id' => CurrentUser::obj()->id,
        ];
        $backendSortArray = [
            ['notification.is_shown', 'ASC'],
            ['notification.created_at', 'DESC'],
        ];
        $q                = $this->model->getAllWithReferencesPart1($conditions);
        $collection       = $this->model->getAllWithReferencesPart2($backendSortArray, $pageSze, $pageNumber);
        #####
        echo (new htmlAlias)->page($collection);
    }

    public function actionMarkAsShownEarlierThan($timestamp)
    {
        $CU         = CurrentUser::obj();
        $cuId       = $CU->id;
        $data       = (object)[
            'is_shown' => '1',
        ];
        $conditions = [
            'to_id' => $cuId,
            ['created_at', '<=', $timestamp],
        ];
        $this->model->update($data, $conditions);
        echo (new htmlAlias)->page([]);
    }

    public function actionDelete($id = NULL)
    {
        $affectedRows = 0;
        if (Request::isPostPutDelete($post)) {
            $conditions = [];
            if ($id) {
                $conditions['id'] = $id;
            }
            $conditions['to_id'] = CurrentUser::obj()->id;
            $affectedRows        = $this->model->delete($conditions);
            if ($affectedRows < 1) {
                AlinaResponseSuccess(0);
            }
        }
        echo (new htmlAlias)->page([]);
    }
}