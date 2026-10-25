<?php

namespace alina\mvc\Controller;

use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\like as mLike;
use alina\mvc\Model\notification;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Request as Request;

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

        if (Request::obj()->isPostPutDelete($post)) {
            $ref_table  = $post->ref_table;
            $ref_id     = $post->ref_id;
            $val        = (int) $post->val;
            if (! in_array($val, [-1, 1], true)) {
                throw new \InvalidArgumentException('Reaction must be either 1 or -1.');
            }
            $mLikeAttrs = $this->model->getOne([
                'ref_table' => $ref_table,
                'ref_id'    => $ref_id,
                'user_id'   => CurrentUser::obj()->id(),
            ]);

            ###
            # Remove the selected reaction, or switch to the opposite reaction.
            if (! empty($mLikeAttrs->id) && (int) $mLikeAttrs->val === $val) {
                $this->model->deleteById($mLikeAttrs->id);
                (new notification())->delete([
                    'bind_tbl' => 'like',
                    'bind_id'  => $mLikeAttrs->id,
                ]);
                $CurrentUserReaction = 0;
            } elseif (! empty($mLikeAttrs->id)) {
                $this->model->updateById(['id' => $mLikeAttrs->id, 'val' => $val]);
                (new notification())->delete([
                    'bind_tbl' => 'like',
                    'bind_id'  => $mLikeAttrs->id,
                ]);
                $CurrentUserReaction = $val;
                $this->addNotification($post, $ref_id, $mLikeAttrs->id, $val);
            }
            ###
            # Add the selected reaction.
            else {
                $mLike = $this->model;
                $mLike->insert($post);
                $CurrentUserReaction = $val;
                $this->addNotification($post, $ref_id, $mLike->id, $val);
            }
            $reactionCounts = (new mLike())
                ->q()
                ->selectRaw('val, COUNT(*) AS amount')
                ->where([
                    'ref_table' => $ref_table,
                    'ref_id'    => $ref_id,
                ])
                ->whereIn('val', [-1, 1])
                ->groupBy('val')
                ->pluck('amount', 'val');
            $vd = (object)[
                'CurrentUserReaction' => $CurrentUserReaction,
                'CurrentUserLiked'    => $CurrentUserReaction === 1 ? 1 : 0,
                'AmountLikes'         => (int) ($reactionCounts[1] ?? 0),
                'AmountDislikes'      => (int) ($reactionCounts[-1] ?? 0),
            ];
            AlinaEcho((new htmlAlias())->page($vd));
        }
    }

    #region SELECT
    public function actionSelectList($pageSze = 10, $page = 1, $ref_table = 'tale', $ref_id = null)
    {
        $backendSortArray = [['lk.created_at', 'DESC']];
        $conditions       = ['lk.ref_table' => $ref_table, 'lk.ref_id' => $ref_id];
        $q                = $this->model->getAllWithReferencesPart1($conditions);
        $collection       = $this->model->getAllWithReferencesPart2($backendSortArray, $pageSze, $page);
        AlinaEcho((new htmlAlias())->page($collection));
    }

    #endregion SELECT
    #####

    private function addNotification(object $post, int $ref_id, int $likeId, int $reaction): void
    {
        $chainOfParents    = (new \alina\mvc\Model\tale())->getChainOfParents($ref_id);
        $to_id             = $chainOfParents->owner_id;
        $root_tale_id      = $chainOfParents->root_tale_id ?: $ref_id;
        $answer_to_tale_id = $chainOfParents->answer_to_tale_id ?: $ref_id;
        $highlight         = $ref_id;
        $url               = "/tale/upsert/{$root_tale_id}?highlight={$highlight}&expand={$answer_to_tale_id}";
        $text              = $reaction === 1 ? "You are liked!" : "You are disliked!";
        (new notification())->insert((object)[
            'to_id'        => $to_id,
            'from_id'      => CurrentUser::obj()->id(),
            'txt'          => $text,
            'link'         => $url,
            'id_root'      => $root_tale_id,
            'id_answer'    => $answer_to_tale_id,
            'id_highlight' => $ref_id,
            'tbl'          => $post->ref_table,
            'bind_tbl'     => 'like',
            'bind_id'      => $likeId,
        ]);
    }
}
