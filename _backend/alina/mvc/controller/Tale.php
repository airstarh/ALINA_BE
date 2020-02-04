<?php

namespace alina\mvc\controller;

use alina\Message;
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
            'header'       => '***',
            'body'         => 'text',
            'publish_at'   => '',
            'is_submitted' => 0,
        ];
        ##################################################
        if (empty($id)) {
            if (Request::isPostPutDelete($post)) {
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
            }
            Sys::redirect("/tale/upsert/{$mTale->id}", 303);
        }
        if (Request::isPostPutDelete($post)) {
            $vd = Data::mergeObjects($vd, $mTaleAttrs, $post);
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
}
