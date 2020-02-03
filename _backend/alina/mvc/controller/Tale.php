<?php

namespace alina\mvc\controller;

use alina\mvc\model\CurrentUser;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
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
        $mTale = new \alina\mvc\model\tale();
        $vd    = (object)[
            'id'           => NULL,
            'form_id'      => __FUNCTION__,
            'header'       => '',
            'body'         => '',
            'publish_at'   => '',
            'is_submitted' => 0,
        ];
        if ($id) {
            $mTaleAttrs = $mTale->getById($id);
        } else {
            $mTaleAttrs = $mTale->getOne(['is_submitted' => 1, 'owner_id' => CurrentUser::obj()->id,]);
            if (!$mTaleAttrs->id) {
                $mTaleAttrs = $mTale->insert($vd);
            }
            Sys::redirect("/tale/upsert/{$mTale->id}", 303);
        }
        if (Request::isPostPutDelete($post)) {
            $vd               = Data::mergeObjects($vd, $mTaleAttrs, $post);
            $vd->is_submitted = 1;
            $mTale->updateById($vd);
        }
        echo (new htmlAlias)->page($vd);

        return $this;
    }
}
