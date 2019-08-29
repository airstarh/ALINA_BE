<?php

namespace alina\mvc\controller;

use alina\mvc\model\DataPlayer;
use alina\mvc\view\html as htmlAlias;

class ArraySerialize
{
    /**
     * http://alinazero/ArraySerialize/index
     */
    public function actionIndex()
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
