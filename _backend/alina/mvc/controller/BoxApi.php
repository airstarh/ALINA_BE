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
        #####
        $vd = (object)[
            //'file'          => $this->srvBoxApi->searchFileByName('fc80d59877b4ae21911591b53664b2da1324cf25-PDF_1_PAGE.pdf', 0),
            //'objFile'       => $objFile,
            //'delete'        => $this->srvBoxApi->requestDeleteAllFilesInFolder(0),
            //'folder0'       => $this->srvBoxApi->requestFolder(0),
            'strUrlPreview' => $this->srvBoxApi->requestPreview($objFile),
            //'list' => $this->srvBoxApi->requestFileList(0),
        ];
        #####
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
    }
}
