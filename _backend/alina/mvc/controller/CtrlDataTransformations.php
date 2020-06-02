<?php

namespace alina\mvc\controller;

use alina\app;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Request;

class CtrlDataTransformations
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    ##################################################
    ##################################################
    ##################################################
    /**
     * http://alinazero/CtrlDataTransformations/index
     * @file _backend/alina/mvc/template/CtrlDataTransformations/actionJson.php
     */
    public function actionJson()
    {
        ##################################################
        $vd   = (object)[
            'form_id'           => __FUNCTION__,
            'strSource'         => '{}',
            'strFrom'           => '',
            'strTo'             => '',
            'strRes'            => '',
            'mxdJsonDecoded'    => '',
            'mxdResJsonDecoded' => '',
            'tCount'            => 0,
        ];
        $data = (object)[];
        ##################################################
        if (Request::isPost()) {
            $p         = \alina\utils\Data::deleteEmptyProps(Request::obj()->POST);
            $vd        = \alina\utils\Data::mergeObjects($vd, $p);
            $strSource = $vd->strSource;
            $strFrom   = $vd->strFrom;
            $strTo     = $vd->strTo;
            $data      = Data::jsonSearchReplace($strSource, $strFrom, $strTo);
        }
        ##################################################
        $vd = \alina\utils\Data::mergeObjects($vd, $data);
        echo (new htmlAlias)->page($vd);

        return $this;
    }

    ##################################################
    ##################################################
    ##################################################
}
