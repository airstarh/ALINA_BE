<?php

namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\tale as taleAlias;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\Data;
use alina\utils\Obj;
use alina\utils\Request;
use alina\utils\Sys;
use Illuminate\Database\Query\Builder as BuilderAlias;

class Tale
{
    /**
     * @route /tale/uosert
     * @route /Generic/index/test/path/parameters
     * @param null $id
     * @throws \alina\exceptionValidation
     */
    public function actionUpsert($id = NULL)
    {
        //ToDo: Checks if allowed to comment etc
        $mTale  = new taleAlias();
        $vd     = (object)[
            'id'           => NULL,
            'form_id'      => __FUNCTION__,
            'header'       => '***',
            'body'         => 'text',
            'publish_at'   => ALINA_TIME,
            'is_submitted' => 0,
        ];
        $isPost = Request::isPostPutDelete($post);
        ##################################################
        if (empty($id)) {
            if ($isPost) {
                if (isset($post->id)) {
                    $id = $post->id;
                }
            }
        }
        ########################################
        if ($id) {
            $attrs = $mTale->getById($id);
        } else {
            $attrs = $mTale->getOne(['is_submitted' => 0, 'owner_id' => CurrentUser::obj()->id,]);
            if (!$attrs->id) {
                $attrs = $mTale->insert($vd);
                Sys::redirect("/tale/upsert/{$mTale->id}", 307);
            }
        }
        ########################################
        if ($isPost) {
            $vd = Data::mergeObjects(
                $vd,
                $attrs,
                Data::deleteEmptyProps($post)
            );
            if (AlinaAccessIfAdminOrModeratorOrOwner($vd->owner_id)) {
                $vd->is_submitted = 1;
                $attrs            = $mTale->updateById($vd);
            } else {
                AlinaResponseSuccess(0);
                Message::setDanger('Forbidden');
            }
        }
        ########################################
        $attrs = $mTale->getOneWithReferences([["{$mTale->alias}.{$mTale->pkName}", $attrs->id]]);
        $vd    = Data::mergeObjects($vd, $attrs);
        echo (new htmlAlias)->page($vd);
    }

    ########################################
    public function actionDelete($id = NULL)
    {
        $vd     = (object)[
            'form_id' => __FUNCTION__,
        ];
        $isPost = Request::isPostPutDelete($post);
        ##################################################
        if ($isPost && $id && (AlinaAccessIfAdminOrModeratorOrOwner($post->owner_id))) {
            $vd->comments1 = (new taleAlias())->delete(['root_tale_id' => $id,]);
            $vd->comments3 = (new taleAlias())->delete(['answer_to_tale_id' => $id,]);
            $vd->rows      = (new taleAlias())->deleteById($id);
            Message::set('Deleted');
        } else {
            AlinaResponseSuccess(0);
            Message::setDanger('Failed');
        }
        ########################################
        echo (new htmlAlias)->page($vd);

        return $this;
    }

    ########################################
    ########################################
    ########################################
    /**
     * @param int $pageSze
     * @param int $page
     * @param array $answer_to_tale_ids
     * @route /tale/feed
     * @route /tale/feed/5/1/125
     */
    public function actionFeed($pageSze = 5, $page = 1, $answer_to_tale_ids = [])
    {
        $vd = (object)[
            'tale' => [],
        ];
        ########################################
        $conditions[] = ["tale.is_submitted", '=', 1];
        $conditions[] = ["tale.publish_at", '<=', ALINA_TIME];
        ####################
        if (empty($answer_to_tale_ids)) {
            ####################
            #region POSTS
            $conditions[] = ["tale.type", '=', 'POST'];
            $sort[]       = ["tale.publish_at", 'DESC'];
            #endregion POSTS
            ####################
        } else {
            ####################
            #region COMMENTS
            $sort[] = ["tale.publish_at", 'ASC'];
            #endregion COMMENTS
            ####################
        }
        $collection = $this->processResponse($conditions, $sort, $pageSze, $page, $answer_to_tale_ids);
        ########################################
        $vd->tale = $collection->toArray();
        ########################################
        echo (new htmlAlias)->page($vd);
    }

    ########################################
    protected function processResponse($conditions = [], $sort = [], $pageSize = 5, $pageCurrentNumber = 1, $answer_to_tale_ids = [], $paginationVersa = FALSE)
    {
        $mTale = new taleAlias();
        $q     = $mTale->getAllWithReferencesPart1($conditions);
        if (!empty($answer_to_tale_ids)) {
            ####################
            #region COMMENTS
            if (!is_array($answer_to_tale_ids)) {
                $answer_to_tale_ids = [$answer_to_tale_ids];
            }
            $q->whereIn('tale.answer_to_tale_id', $answer_to_tale_ids);
            $paginationVersa = TRUE;
            #endregion COMMENTS
            ####################
        } else {
            ####################
            #region POSTS
            if (Request::has('txt', $txt)) {
                $txt = trim($txt);
                if (!empty($txt)) {
                    $q->where(function ($q) use ($txt) {
                        /** @var $q BuilderAlias object */
                        $q->where("tale.body_txt", 'LIKE', "%{$txt}%")
                            ->orWhere("tale.header", 'LIKE', "%{$txt}%")
                            ->orWhere("owner.firstname", 'LIKE', "%{$txt}%")
                            ->orWhere("owner.lastname", 'LIKE', "%{$txt}%");
                    });
                }
            }
            #endregion POSTS
            ####################
        }
        $collection = $mTale->getAllWithReferencesPart2($sort, $pageSize, $pageCurrentNumber, $paginationVersa);

        return $collection;
    }
    ########################################
    ########################################
    ########################################
    ########################################
}
