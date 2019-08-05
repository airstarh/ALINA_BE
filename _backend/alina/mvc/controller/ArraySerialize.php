<?php

namespace alina\mvc\controller;

class ArraySerialize
{
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
            $arrRes[$k] = $v;
        }
        $strRes = serialize($arrRes);

        $data = (object)[
            'strSource' => $strSource,
            'strRes'    => $strRes,
            'arrRes'    => $arrRes,
        ];
        echo (new \alina\mvc\view\html)->page($data);
        return $this;
    }
}
