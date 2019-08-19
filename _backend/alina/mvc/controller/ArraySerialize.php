<?php

namespace alina\mvc\controller;

class ArraySerialize
{
    /**
     * http://alinazero/ArraySerialize/index
     */
    public function actionIndex()
    {
        $strSource = '';
        $arrSource = [];
        $strRes    = '';
        $arrRes    = [];
        $post      = resolvePostDataAsObject();
        $strFrom   = (isset($post->strFrom) && !empty($post->strFrom)) ? $post->strFrom : '';
        $strTo     = (isset($post->strTo) && !empty($post->strTo)) ? $post->strTo : '';
        if (isset($post->strSource) && !empty($post->strSource)) {
            $strSource = $post->strSource;
            $arrSource = unserialize($strSource);
        }
        $tCount = 0;
        foreach ($arrSource as $k => $v) {
            #region Some modification Staff here
            // ...
            $v      = str_replace($strFrom, $strTo, $v, $iCount);
            $tCount += $iCount;
            #endregion Some modification Staff here
            $arrRes[$k] = $v;
        }
        $strRes        = serialize($arrRes);
        $arrResControl = unserialize($strRes);
        $strResControl = serialize($arrResControl);

        $data = (object)[
            'strSource'     => $strSource,
            'strRes'        => $strRes,
            'arrRes'        => $arrRes,
            'arrResControl' => $arrResControl,
            'strResControl' => $strResControl,
            'strFrom'       => $strFrom,
            'strTo'         => $strTo,
            'tCount'        => $tCount,
        ];
        echo (new \alina\mvc\view\html)->page($data);

        return $this;
    }
}
