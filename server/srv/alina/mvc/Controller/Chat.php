<?php

namespace alina\mvc\Controller;

use alina\Message;
use alina\mvc\View\html as html;
use alina\Utils\Request;

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
        // Message::setDanger('Test');
        $vd = (object)[
            'args'    => $arg,
            'channel' => (string) (Request::obj()->GET->channel ?? '1'),
        ];

        AlinaEcho((new html())->page($vd, html::$htmlLayoutVirgin));

        return $this;
    }
}
