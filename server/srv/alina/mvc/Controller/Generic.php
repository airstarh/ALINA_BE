<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html as htmlAlias;

class Generic
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
        AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutWide));

        return $this;
    }
}
