<?php

namespace alina\mvc\model;

class DataPlayer
{
    public function serializedArraySearchReplace($strSource, $strFrom = '', $strTo = '')
    {
        #region Defaults
        $data = (object)[
            'strSource'     => '',
            'strRes'        => '',
            'arrRes'        => [],
            'arrResControl' => [],
            'strResControl' => '',
            'strFrom'       => '',
            'strTo'         => '',
            'tCount'        => 0,
        ];

        #endregion Defaults
        $arrSource = (isset($strSource) && !empty($strSource)) ? hlpSuperUnSerialize($strSource) : [];
        if (FALSE == $arrSource) {
            return $data;
        }
        $arrRes    = [];
        $tCount    = 0;
        foreach ($arrSource as $k => $v) {
            #region Some modification Staff here
            if (FALSE !== hlpSuperUnSerialize($v)) {
                $d      = $this->serializedArraySearchReplace($strSource, $strFrom, $strTo);
                $v      = $d->strResControl;
                $iCount = $d->tCount;
            } else {
                $v = str_replace($strFrom, $strTo, $v, $iCount);
            }
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

        return $data;
    }
}
