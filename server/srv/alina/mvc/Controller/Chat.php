<?php

namespace alina\mvc\Controller;

use alina\Message;
use alina\mvc\View\html as html;

class Chat
{
    public function __construct()
    {
        // AlinaRejectIfNotAdmin();
    }

    /**
     * @route /Chat/index
     */
    public function actionIndex(...$arg)
    {
        Message::setDanger('Test');
        $vd = [
            'args' => $arg,
        ];
        #####
        AlinaEcho((new html())->page($vd, html::$htmlLayoutVirgin));

        return $this;
    }
}
