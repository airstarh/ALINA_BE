<?php

namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\tale as taleAlias;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Obj;
use alina\utils\Request;
use alina\utils\Sys;

class Tale
{
    /**
     * @route /tale/uosert
     * @route /Generic/index/test/path/parameters
     */
    public function actionUpsert($id = NULL)
    {
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
            $mTaleAttrs = $mTale->getById($id);
        } else {
            $mTaleAttrs = $mTale->getOne(['is_submitted' => 0, 'owner_id' => CurrentUser::obj()->id,]);
            if (!$mTaleAttrs->id) {
                $mTaleAttrs = $mTale->insert($vd);
                Message::set('Inserted new tale');
                Sys::redirect("/tale/upsert/{$mTale->id}", 307);
            }
        }
        if ($isPost) {
            $vd = Data::mergeObjects(
                $vd,
                $mTaleAttrs,
                Data::deleteEmptyProps($post)
            );
            if (
                AlinaAccessIfOwner($vd->owner_id)
                ||
                AlinaAccessIfAdmin()
                ||
                AlinaAccessIfModerator()
            ) {
                $vd->is_submitted = 1;
                $mTaleAttrs       = $mTale->updateById($vd);
                Message::setInfo('Success');
            } else {
                Message::setDanger('Edit of tale is not allowed');
            }
        }
        $vd = Data::mergeObjects($vd, $mTaleAttrs);
        echo (new htmlAlias)->page($vd);

        return $this;
    }
    ########################################
    ########################################
    ########################################
    public function actionFeed()
    {
        $vd = (object)[
            'tales' => [],
        ];
        ########################################
        $conditions = [];
        $sort       = [];
        $limit      = 50;
        $offset     = 50;
        $collection = $this->processFeed($conditions, $sort, $limit, $offset);
        ########################################
        $vd = (object)[
            'tales' => $collection->toArray(),
        ];
        ########################################
        echo (new htmlAlias)->page($vd);
    }

    ########################################
    protected function processFeed($conditions = [], $sort = [], $limit = 50, $offset = 0)
    {
        ########################################
        $mTale      = new taleAlias();
        $conditions = [];
        $sort[]     = ['publish_at', 'DESC'];
        $collection = $mTale->getAllWithReferences($conditions, $sort);

        ########################################
        return $collection;
    }
    ########################################
    ########################################
    ########################################

    ########################################
    ########################################
    ########################################
}
