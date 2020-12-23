<?php

namespace alina\mvc\controller;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\mvc\model\_baseAlinaEloquentTransaction as Transaction;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\notification;
use alina\mvc\model\tale as taleAlias;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\Data;
use alina\utils\Obj;
use alina\utils\Request;
use alina\utils\Sys;
use alina\Watcher;
use Illuminate\Database\Capsule\Manager as Dal;
use Illuminate\Database\Query\Builder as BuilderAlias;

class Tale
{
    /**
     * @route /tale/upsert
     * @route /Generic/index/test/path/parameters
     * @param null $id
     * @throws \alina\AppExceptionValidation
     */
    public function actionUpsert($id = NULL)
    {
        $mTale  = new taleAlias();
        $vd     = (object)[
            'id'           => NULL,
            'form_id'      => __FUNCTION__,
            'header'       => '***',
            'body'         => '',
            'publish_at'   => 0,
            'is_submitted' => 0,
        ];
        $attrs  = (object)[];
        $isGet  = Request::isGet($get);
        $isPost = Request::isPostPutDelete($post);
        ##################################################
        if (empty($id)) {
            AlinaRejectIfNotLoggedIn();
            if ($isPost) {
                if (isset($post->id)) {
                    $id = $post->id;
                    Sys::redirect("/tale/upsert/{$id}", 307);
                }
            }
        }
        ########################################
        if ($id) {
            $attrs = $mTale->getById($id);
            $id    = $attrs->id;
        }
        if (empty($id)) {
            $attrs = $mTale->getOne(['is_submitted' => 0, 'owner_id' => CurrentUser::obj()->id,]);
            if (!isset($attrs->id) || empty($attrs->id)) {
                $attrs = $mTale->insert($vd);
            }
            $id = $attrs->id;
            Sys::redirect("/tale/upsert/{$id}", 307);
        }
        ########################################
        ########################################
        ########################################
        if ($isPost) {
            AlinaRejectIfNotLoggedIn();
            $vd = Data::mergeObjects(
                $vd,
                $attrs,
                $post
            );
            if (AlinaAccessIfAdminOrModeratorOrOwner($vd->owner_id)) {
                Transaction::begin(__FUNCTION__);
                #####
                #region CHECK iF UPDATE or INSERT
                $isNew     = $vd->is_submitted == 0 || empty($vd->is_submitted);
                $isComment = isset($vd->answer_to_tale_id) && !empty($vd->answer_to_tale_id);
                $isPost    = !$isComment;
                /**
                 * NEW
                 */
                if ($isNew) {
                    /**
                     * new Comment
                     */
                    if ($isComment) {
                    }
                    /**
                     * new Tale
                     */
                    else {
                        ##################################################
                        #region WATCH quantity
                        $wmp = $this->watchMaxPosts();
                        if ($wmp->isDenied) {
                            AlinaReject(NULL, 303, "In the  last 24 hours\nPosted: {$wmp->done}\nMax posts allowed: {$wmp->max}");
                        }
                        #endregion WATCH quantity
                        ##################################################
                    }
                    $vd->created_at = ALINA_TIME;
                    $vd->publish_at = ALINA_TIME;
                }
                /**
                 * UPDATE
                 */
                else {
                    /**
                     * UPDATE Comment
                     */
                    if ($isComment) {
                    }
                    /**
                     * UPDATE Tale
                     */
                    else {
                    }
                }
                #endregion CHECK iF UPDATE or INSERT
                #####
                $vd->is_submitted = 1;
                ##################################################
                $attrs = $mTale->updateById($vd);
                ##################################################
                #region Custom route-alias processing
                //ToDo: ROLES!!!
                if (isset($attrs->router_alias) && !empty($attrs->router_alias)) {
                }
                #emdregion Custom route-alias processing
                ##################################################
                #region Notification
                if ($isComment) {
                    $allCommenters = (new \alina\mvc\model\tale())
                        ->q('commenters')
                        ->where(['root_tale_id' => $attrs->root_tale_id,])
                        ->orWhere(['answer_to_tale_id' => $attrs->answer_to_tale_id,])
                        ->orWhere(['root_tale_id' => $attrs->id,])
                        ->orWhere(['answer_to_tale_id' => $attrs->id,])
                        ->orWhere(['id' => $attrs->root_tale_id,])
                        ->distinct()
                        ->pluck('owner_id');
                    $url           = "/#/tale/upsert/{$attrs->root_tale_id}?highlight={$attrs->id}&expand={$attrs->answer_to_tale_id}";
                    $text          = "Comment! Tale ID# {$attrs->root_tale_id}";
                    $tag           = "<a href={$url} class='btn btn-primary mb-2'>{$text}</a>";
                    foreach ($allCommenters as $humanId) {
                        if ($humanId == CurrentUser::obj()->id) {
                            continue;
                        }
                        (new notification())->insert((object)[
                            'to_id'        => $humanId,
                            'from_id'      => CurrentUser::obj()->id,
                            'txt'          => $tag,
                            'link'         => $url,
                            'id_root'      => $attrs->root_tale_id,
                            'id_answer'    => $attrs->id,
                            'id_highlight' => $attrs->answer_to_tale_id,
                            'tbl'          => 'tale',
                        ]);
                    }
                }
                #endregion Notification
                ##################################################
                Transaction::commit(__FUNCTION__);
            }
            else {
                AlinaResponseSuccess(0);
                Message::setDanger('Forbidden');
            }
        }
        ########################################
        $attrs = $mTale->getOneWithReferences([["{$mTale->alias}.{$mTale->pkName}", $attrs->id]]);
        $vd    = Data::mergeObjects($vd, $attrs);
        GlobalRequestStorage::obj()->set('pageTitle', $attrs->header);
        GlobalRequestStorage::obj()->set('pageDescription', mb_substr($attrs->body_txt, 0, 100));
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
            _baseAlinaEloquentTransaction::begin();
            $vd->notifications = (new notification())
                ->q(-1)
                ->where('tbl', '=', 'tale')
                ->where(function ($q) use ($id) {
                    /** @var $q BuilderAlias object */
                    $q
                        ->where('id_root', '=', $id)
                        ->orWhere('id_answer', '=', $id)
                        ->orWhere('id_highlight', '=', $id);
                })
                ->delete();
            ###
            $all       = (new \alina\mvc\model\tale())
                ->q('commenters')
                ->where(['root_tale_id' => $id,])
                ->where(['answer_to_tale_id' => $id,])
                ->orWhere(['id' => $id,])
                ->distinct()
                ->pluck('id');
            $vd->likes = (new \alina\mvc\model\like())
                ->q(-1)
                ->where('ref_table', '=', 'tale')
                ->whereIn('ref_id', $all)
                ->delete();
            ###
            $vd->comments1 = (new taleAlias())->delete(['root_tale_id' => $id,]);
            $vd->comments3 = (new taleAlias())->delete(['answer_to_tale_id' => $id,]);
            $vd->rows      = (new taleAlias())->deleteById($id);
            _baseAlinaEloquentTransaction::commit();
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
        }
        else {
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
            #####
            if (Request::has('expand', $expand)) {
                $expand = trim($expand);
                if (!empty($expand) && is_numeric($expand)) {
                    $q->where(function ($q) use ($expand) {
                        /** @var $q BuilderAlias object */
                        $q->where("tale.id", '=', $expand);
                    });
                }
            }
            #####
            #endregion COMMENTS
            ####################
        }
        else {
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
            #####
            if (Request::has('owner', $owner)) {
                $owner = trim($owner);
                if (!empty($owner) && is_numeric($owner)) {
                    $q->where(function ($q) use ($owner) {
                        /** @var $q BuilderAlias object */
                        $q->where("tale.owner_id", '=', $owner);
                    });
                }
            }
            #endregion POSTS
            ####################
            ####################
        }
        ####################
        $collection = $mTale->getAllWithReferencesPart2($sort, $pageSize, $pageCurrentNumber, $paginationVersa);

        return $collection;
    }

    ########################################
    public function watchMaxPosts()
    {
        $done      = $this->countTalesPosted();
        $max       = $this->getMaxTale();
        $left      = $max - $done;
        $isDenied  = $max !== -1 && $done >= $max;
        $isAllowed = $max === -1 || $done < $max;

        return (object)[
            'max'       => $max,
            'done'      => $done,
            'left'      => $left,
            'isDenied'  => $isDenied,
            'isAllowed' => $isAllowed,
        ];
    }

    public function getMaxTale($uid = NULL)
    {
        if (empty($uid)) {
            $CU = CurrentUser::obj();
        }
        else {
            $CU        = new user();
            $CU->id    = $uid;
            $CU->alias = "user_{$uid}";
        }
        $cfg = AlinaCfg('watcher/newTale/max');
        /*[
            'registered' => 3,
            'admin'      => -1,
            'moderator'  => -1,
            'privileged' => 10,
        ];*/
        $max = 0;
        if ($CU->hasRole('admin')) {
            $max = $cfg['admin'];
        }
        else if ($CU->hasRole('moderator')) {
            $max = $cfg['moderator'];
        }
        else if ($CU->hasRole('privileged')) {
            $max = $cfg['privileged'];
        }
        else if ($CU->hasRole('registered')) {
            $max = $cfg['registered'];
        }

        return $max;
    }

    public function countTalesPosted($uid = NULL)
    {
        #####
        if (empty($uid)) {
            $uid = CurrentUser::obj()->id;
        }
        #####
        $mAmount = new \alina\mvc\model\tale();
        $qAmount = $mAmount
            ->q(-1)
            ->where([
                'owner_id'     => $uid,
                'is_submitted' => 1,
                'type'         => 'POST',
                ['created_at', '>=', ALINA_TIME - 60 * 60 * 24],
            ])
            ->count();

        return $qAmount;
    }

    ########################################
    ########################################
    ########################################
}
