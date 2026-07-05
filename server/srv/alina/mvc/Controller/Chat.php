<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html as html;

class Chat
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    /**
     * @route /Generic/index
     * @route /Generic/index/test/path/parameters
     */
    public function actionIndex(...$arg)
    {
        $vd = [
            'args' => $arg,
        ];
        #####
        AlinaEcho((new html())->page($vd, html::$htmLayoutWide));

        return $this;
    }
}
