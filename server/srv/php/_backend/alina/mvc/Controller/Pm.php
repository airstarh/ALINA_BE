<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html as htmlAlias;

class Pm
{
    public function __construct()
    {

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

        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);

        return $this;
    }
}
