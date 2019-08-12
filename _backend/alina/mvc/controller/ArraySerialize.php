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
        if (isset($post->strSource) && !empty($post->strSource)) {
            $strSource = $post->strSource;
            $arrSource = unserialize($strSource);
        }
        foreach ($arrSource as $k => $v) {
            #region Some modification Staff here
            // ...
            #endregion Some modification Staff here
            $arrRes[$k] = $v;
        }
        $strRes = serialize($arrRes);

        $arrResControl = unserialize($strRes);
        $strResControl = serialize($arrResControl);

        $data = (object)[
            'strSource'     => $strSource,
            'strRes'        => $strRes,
            'arrRes'        => $arrRes,
            'arrResControl' => $arrResControl,
            'strResControl' => $strResControl,
        ];
        echo (new \alina\mvc\view\html)->page($data);

        return $this;
    }
}
