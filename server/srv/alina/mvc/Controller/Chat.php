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
     * @route /Chat/index
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
