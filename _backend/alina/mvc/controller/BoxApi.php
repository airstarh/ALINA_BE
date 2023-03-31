<?php

namespace alina\mvc\controller;

use alina\mvc\view\html as htmlAlias;
use alina\services\thirdPart\BoxApi\BoxService;

class BoxApi
{
    private $srvBoxApi;

    public function __construct()
    {
        //AlinaRejectIfNotAdmin();
        $this->srvBoxApi = new BoxService();
    }

    /**
     * @route /Generic/index
     * @route /Generic/index/test/path/parameters
     */
    public function actionIndex(...$arg)
    {
        $vd = [
            'boxApiConfig' => $this->srvBoxApi->getBoxApiConfig(),
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
    #########################
    #region CONFIGURATION
    public function boxApiConfig()
    {
        ##### \alina\services\thirdPart\BoxApi\BoxService::getBoxApiConfig
    }
    #endregion CONFIGURATION
    #########################
}
