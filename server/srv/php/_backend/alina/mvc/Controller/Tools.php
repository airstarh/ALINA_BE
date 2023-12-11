<?php

namespace alina\mvc\Controller;

use alina\GlobalRequestStorage;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\Request;

class Tools
{
    /**
     * @route /tools/SerializedDataEditor
     */
    public function actionSerializedDataEditor()
    {
        ##################################################
        $vd   = (object)[
            'form_id'         => __FUNCTION__,
            'strSource'       => '',
            'mixedSource'     => '',
            'strRes'          => '',
            'mixedRes'        => [],
            'mixedResControl' => [],
            'strResControl'   => '',
            'strFrom'         => '',
            'strTo'           => '',
            'tCount'          => 0,
        ];
        $data = (object)[];
        ##################################################
        if (Request::isPost($post)) {
            $p         = $post;
            $vd        = Data::mergeObjects($vd, $p);
            $strFrom   = $vd->strFrom;
            $strTo     = $vd->strTo;
            $strSource = $vd->strSource;
            $data      = Data::serializedDataSearchReplace($strSource, $strFrom, $strTo);
        }
        ##################################################
        GlobalRequestStorage::obj()->set('pageTitle', 'PHP-Serialized Data Editor online');
        $vd = \alina\Utils\Data::mergeObjects($vd, $data);
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);

        return $this;
    }

    #####

    /**
     * @file _backend/alina/mvc/template/CtrlDataTransformations/actionJson.php
     */
    public function actionJsonSearchReplaceBeautify()
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
        if (Request::isPost($post)) {
            $p         = $post;
            $vd        = \alina\Utils\Data::mergeObjects($vd, $p);
            $strSource = $vd->strSource;
            $strFrom   = $vd->strFrom;
            $strTo     = $vd->strTo;
            $data      = Data::jsonSearchReplace($strSource, $strFrom, $strTo);
        }
        ##################################################
        GlobalRequestStorage::obj()->set('pageTitle', 'JSON Search-Replace-Beautify online');
        $vd = \alina\Utils\Data::mergeObjects($vd, $data);
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);

        return $this;
    }
}
