<?php

namespace alina\mvc\controller;

use alina\mvc\model\CurrentUser;
use alina\mvc\model\like as mLike;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Request as Request;
use alina\mvc\model\notification;

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
            $ref_table  = $post->ref_table;
            $ref_id     = $post->ref_id;
            $val        = $post->val;
            $mLikeAttrs = $this->model->getOne([
                'ref_table' => $ref_table,
                'ref_id'    => $ref_id,
                'user_id'   => CurrentUser::obj()->id,
            ]);
            ###
            #remove Like
            if (isset($mLikeAttrs->id) && !empty($mLikeAttrs->id)) {
                $this->model->deleteById($mLikeAttrs->id);
                (new notification())->delete([
                    'bind_tbl' => 'like',
                    'bind_id'  => $mLikeAttrs->id,
                ]);
                $CurrentUserLiked = 0;
            }
            ###
            #add Like
            else {
                $mLike = $this->model;
                $mLike->insert($post);
                $CurrentUserLiked = 1;
                #####
                #region Add Notification
                $chainOfParents    = (new \alina\mvc\model\tale())->getChainOfParents($ref_id);
                $to_id             = $chainOfParents->owner_id;
                $root_tale_id      = $chainOfParents->root_tale_id ?: $ref_id;
                $answer_to_tale_id = $chainOfParents->answer_to_tale_id ?: $ref_id;
                $highlight         = $ref_id;
                $url               = "/#/tale/upsert/{$root_tale_id}?highlight={$highlight}&expand={$answer_to_tale_id}";
                $text              = "Like!";
                $tag               = "<a href={$url} target=_blank class='btn btn-primary mb-2'>{$text}</a>";
                (new notification())->insert((object)[
                    'to_id'        => $to_id,
                    'from_id'      => CurrentUser::obj()->id,
                    'txt'          => $tag,
                    'link'         => $url,
                    'id_root'      => $root_tale_id,
                    'id_answer'    => $answer_to_tale_id,
                    'id_highlight' => $ref_id,
                    'tbl'          => $post->ref_table,
                    'bind_tbl'     => 'like',
                    'bind_id'      => $mLike->id,
                ]);
                #endregion Add Notification
                #####
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

    #endregion SELECT
    #####
}
