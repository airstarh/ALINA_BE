<?php

namespace alina\mvc\Controller;

use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\tale as taleAlias;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Request;
use Illuminate\Database\Query\Builder as BuilderAlias;

class SiteMap
{
    public function __construct()
    {
        //AlinaRejectIfNotAdmin();
    }

    /**
     * @route /Generic/index
     * @route /Generic/index/test/path/parameters
     */
    public function actionIndex(...$arg)
    {
        $vd = $this->actionFeed();
        echo (new htmlAlias)->piece('/SiteMap/actionIndex.php', $vd);
    }

    protected function actionFeed()
    {
        $vd = (object)[
            'tale' => [],
        ];
        ########################################
        $conditions[] = ["tale.type", '=', 'POST'];
        $conditions[] = ["tale.is_submitted", '=', 1];
        $conditions[] = ["tale.is_for_registered", '=', 0];
        $conditions[] = ["tale.publish_at", '<=', ALINA_TIME];
        $conditions[] = ["tale.header", '!=', '***'];
        ########################################
        $sort[]     = ["tale.publish_at", 'DESC'];
        $collection = $this->processResponse($conditions, $sort);
        ########################################
        $vd->tale = $collection->toArray();

        ########################################
        return $vd;
    }

    protected function processResponse($conditions = [], $sort = [], $pageSize = 25000, $pageCurrentNumber = 1, $answer_to_tale_ids = [], $paginationVersa = FALSE)
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
                          ->orWhere("owner.lastname", 'LIKE', "%{$txt}%")
                        ;
                    });
                }
            }
            #####
            # TODO: May be for comments too.
            array_unshift($sort, ["tale.is_sticked", 'DESC']);
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
            else {
                //$q->where("tale.is_draft", '=', 0);
                $q->where(function ($q) {
                    /** @var $q BuilderAlias object */
                    $q->orWhere("tale.is_draft", '=', 0)
                      ->orWhere("tale.is_draft", '=', '')
                      ->orWhereNull("tale.is_draft")
                    ;
                });
            }
            #endregion POSTS
            ####################
            ####################
        }
        ####################
        $collection = $mTale->getAllWithReferencesPart2($sort, $pageSize, $pageCurrentNumber, $paginationVersa);

        return $collection;
    }
}
