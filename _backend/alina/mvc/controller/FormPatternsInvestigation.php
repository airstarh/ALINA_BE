<?php

namespace alina\mvc\controller;

use alina\mvc\view\html as htmlAlias;

class FormPatternsInvestigation
{
    /**
     * @route /FormPatternsInvestigation/Index
     */
    public function actionIndex()
    {
        $post = resolvePostDataAsObject();
        $get  = resolveGetDataAsObject();
        /////////////////////////////////////



        /////////////////////////////////////
        $data = (object)[
            'post' => $post,
            'get'  => $get,
        ];
        echo (new htmlAlias)->page($data);
    }
}
