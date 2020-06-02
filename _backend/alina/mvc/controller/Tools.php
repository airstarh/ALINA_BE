<?php

namespace alina\mvc\controller;

use alina\GlobalRequestStorage;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Request;

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
            $p         = Data::deleteEmptyProps($post);
            $vd        = Data::mergeObjects($vd, $p);
            $strFrom   = $vd->strFrom;
            $strTo     = $vd->strTo;
            $strSource = $vd->strSource;
            $data      = Data::serializedArraySearchReplace($strSource, $strFrom, $strTo);
        }
        ##################################################
        GlobalRequestStorage::obj()->set('pageTitle', 'Serialized Data Editor online');
        $vd = \alina\utils\Data::mergeObjects($vd, $data);
        echo (new htmlAlias)->page($vd);

        return $this;
    }
}
