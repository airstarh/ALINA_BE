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
    }

    /**
     * @route /Generic/index
     * @route /Generic/index/test/path/parameters
     */
    public function actionIndex(...$arg)
    {
        $this->srvBoxApi = new BoxService();
        $objFile         = (object)[
            'file_id'  => -1,
            'box_id'   => NULL,
            'fullPath' => 'C:\_A001\REPOS\OWN\ALINA\_backend\alina\_MISC_CONTENT\_TEST_FILES_CONTENT\_PDF\PDF_1_PAGE.pdf',
        ];
        $strUrlPreview   = $this->srvBoxApi->retrieveBoxPreviewUrl($objFile);
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
    }

    public function actionBox2023()
    {
        $this->srvBoxApi = new BoxService2023();
        $objFile         = (object)[
            'file_id'  => -1,
            'box_id'   => NULL,
            'fullPath' => 'C:\_A001\REPOS\OWN\ALINA\_backend\alina\_MISC_CONTENT\_TEST_FILES_CONTENT\_PDF\PDF_1_PAGE.pdf',
        ];
        $strUrlPreview   = $this->srvBoxApi->retrieveBoxPreviewUrl($objFile);
        #####
        $vd = (object)[
            'objFile'       => $objFile,
            'strUrlPreview' => $strUrlPreview,
        ];
        #####
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
