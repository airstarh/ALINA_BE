<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html as htmlAlias;

class FormPatternsInvestigation
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    /**
     * @route /FormPatternsInvestigation/Index
     */
    public function actionIndex()
    {
        $post = \alina\Utils\Sys::resolvePostDataAsObject();
        $get  = \alina\Utils\Sys::resolveGetDataAsObject();
        /////////////////////////////////////
        /////////////////////////////////////
        $data = (object)[
            'post' => $post,
            'get'  => $get,
        ];
        echo (new htmlAlias)->page($data);
    }
}
