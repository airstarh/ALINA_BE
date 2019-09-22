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
            'arrRes'        => [],
            'arrResControl' => [],
            'strResControl' => '',
            'strFrom'       => '',
            'strTo'         => '',
            'tCount'        => 0,
        ];
        $p  = hlpEraseEmpty(resolvePostDataAsObject());
        $vd = hlpMergeSimpleObjects($vd, $p);
        ##################################################
        $strFrom   = $vd->strFrom;
        $strTo     = $vd->strTo;
        $strSource = $vd->strSource;
        $data      = (new DataPlayer())->serializedArraySearchReplace($strSource, $strFrom, $strTo);
        ##################################################
        $vd = hlpMergeSimpleObjects($vd, $data);
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
            'strSource'        => '',
            'strFrom'          => '',
            'strTo'            => '',
            'strResBeautified' => '',
            'tCount'           => 0,
        ];
        $p  = hlpEraseEmpty(resolvePostDataAsObject());
        $vd = hlpMergeSimpleObjects($vd, $p);
        ##################################################
        $strSource = $vd->strSource;
        $strFrom   = $vd->strFrom;
        $strTo     = $vd->strTo;
        $data      = (new DataPlayer())->serializedArraySearchReplace($strSource, $strFrom, $strTo);
        ##################################################
        $vd = hlpMergeSimpleObjects($vd, $data);
        echo (new htmlAlias)->page($vd);

        return $this;
    }
}
