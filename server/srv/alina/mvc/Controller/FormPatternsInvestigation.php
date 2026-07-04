<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html as htmlAlias;
use alina\Utils\Request;

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
        $post = Request::obj()->POST;
        $get  = Request::obj()->GET;

        $data = (object)[
            'post' => $post,
            'get'  => $get,
        ];
        AlinaEcho((new htmlAlias())->page($data));
    }
}
