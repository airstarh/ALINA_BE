<?php

namespace alina\mvc\controller;

use alina\app;
use alina\mvc\model\DataPlayer;
use alina\mvc\view\html as htmlAlias;

class CtrlDataTransformations
{
    /**
     * http://alinazero/CtrlDataTransformations/SerializedArrayModification
     * @file _backend/alina/mvc/template/CtrlDataTransformations/actionSerializedArrayModification.php
     */
    public function actionSerializedArrayModification()
    {
        ##################################################
        $vd = (object)[
            'strSource'     => '',
            'strRes'        => '',
            'mixedRes'        => [],
            'mixedResControl' => [],
            'strResControl' => '',
            'strFrom'       => '',
            'strTo'         => '',
            'tCount'        => 0,
        ];
        $p  = \alina\utils\Data::hlpEraseEmpty(\alina\utils\Sys::resolvePostDataAsObject());
        $vd = \alina\utils\Data::hlpMergeSimpleObjects($vd, $p);
        ##################################################
        $strFrom   = $vd->strFrom;
        $strTo     = $vd->strTo;
        $strSource = $vd->strSource;
        $data      = (new DataPlayer())->serializedArraySearchReplace($strSource, $strFrom, $strTo);
        ##################################################
        $vd = \alina\utils\Data::hlpMergeSimpleObjects($vd, $data);
        echo (new htmlAlias)->page($vd);

        return $this;
    }

    /**
     * http://alinazero/CtrlDataTransformations/index
     * @file _backend/alina/mvc/template/CtrlDataTransformations/actionJson.php
     */
    public function actionJson()
    {
        ##################################################
        $vd = (object)[
            'strSource'        => '{}',
            'strFrom'          => '',
            'strTo'            => '',
            'tCount'           => 0,
        ];
        $p  = \alina\utils\Data::hlpEraseEmpty(\alina\utils\Sys::resolvePostDataAsObject());
        $vd = \alina\utils\Data::hlpMergeSimpleObjects($vd, $p);
        ##################################################
        $strSource = $vd->strSource;
        $strFrom   = $vd->strFrom;
        $strTo     = $vd->strTo;
        $data      = (new DataPlayer())->jsonSearchReplace($strSource, $strFrom, $strTo);
        ##################################################
        $vd = \alina\utils\Data::hlpMergeSimpleObjects($vd, $data);
        echo (new htmlAlias)->page($vd);

        return $this;
    }
}
