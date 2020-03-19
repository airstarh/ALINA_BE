<?php

namespace alina\mvc\controller;

use alina\mvc\view\html as htmlAlias;

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
        $post = \alina\utils\Sys::resolvePostDataAsObject();
        $get  = \alina\utils\Sys::resolveGetDataAsObject();
        /////////////////////////////////////
        /////////////////////////////////////
        $data = (object)[
            'post' => $post,
            'get'  => $get,
        ];
        echo (new htmlAlias)->page($data);
    }
}
