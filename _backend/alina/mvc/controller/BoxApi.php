<?php

namespace alina\mvc\controller;

use alina\mvc\view\html as htmlAlias;
use alina\services\thirdPart\BoxApi\BoxService;
use alina\services\thirdPart\BoxApi\BoxService2023;

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
        $objFile       = (object)[
            'file_id'  => -1,
            'box_id'   => NULL,
            'fullPath' => 'C:\_A001\REPOS\OWN\ALINA\_backend\alina\_MISC_CONTENT\_TEST_FILES_CONTENT\_PDF\PDF_1_PAGE.pdf',
        ];
        $strUrlPreview = $this->srvBoxApi->retrieveBoxPreviewUrl($objFile);
        #####
        $vd = (object)[
            'objFile'       => $objFile,
            'strUrlPreview' => $strUrlPreview,
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

    public function actionBox2023()
    {
        #####
        $box = new BoxService2023();

        #####
        $vd = (object)[
            '$box' => $box->egPrimitive(),
        ];
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
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
