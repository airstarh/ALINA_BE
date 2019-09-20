<?php

namespace alina\mvc\controller;

use alina\app;
use alina\mvc\model\DataPlayer;
use alina\mvc\view\html as htmlAlias;

class ArraySerialize
{
    /**
     * http://alinazero/ArraySerialize/index
     */
    public function actionIndex()
    {
        ##################################################
        $vd        = (object)[
            'strSource'     => '',
            'strRes'        => '',
            'arrRes'        => [],
            'arrResControl' => [],
            'strResControl' => '',
            'strFrom'       => '',
            'strTo'         => '',
            'tCount'        => 0,
        ];
        $p         = hlpEraseEmpty(resolvePostDataAsObject());
        $vd        = hlpMergeSimpleObjects($vd, $p);
        ##################################################
        $strFrom   = $vd->strFrom;
        $strTo     = $vd->strTo;
        $strSource = $vd->strSource;
        $data      = (new DataPlayer())->serializedArraySearchReplace($strSource, $strFrom, $strTo);
        ##################################################
        $vd        = hlpMergeSimpleObjects($vd, $data);
        echo (new htmlAlias)->page($vd);

        return $this;
    }

    /**
     * http://alinazero/ArraySerialize/index
     */
    public function actionJson()
    {
        $post      = resolvePostDataAsObject();
        $strFrom   = (isset($post->strFrom) && !empty($post->strFrom)) ? $post->strFrom : '';
        $strTo     = (isset($post->strTo) && !empty($post->strTo)) ? $post->strTo : '';
        $strSource = (isset($post->strSource) && !empty($post->strSource)) ? $post->strSource : '';
        $data      = (new DataPlayer())->serializedArraySearchReplace($strSource, $strFrom, $strTo);
        echo (new htmlAlias)->page($data);

        return $this;
    }
}
