<?php

namespace alina\mvc\controller;

use alina\mvc\view\html as htmlAlias;

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
        // echo '<div class="ck-content">';
        // echo '<pre>';
        // print_r($vd);
        // echo '</pre>';
        // echo '</div>';
        #####
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);

        return $this;
    }
}
