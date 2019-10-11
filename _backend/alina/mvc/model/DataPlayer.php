<?php

namespace alina\mvc\model;

use alina\message;
use alina\utils\Data;

class DataPlayer
{
    public function serializedArraySearchReplace($strSource, $strFrom = '', $strTo = '', &$tCount = 0, $flagRenameKeysAlso = FALSE)
    {
        #region Defaults
        $data = (object)[
            'strSource'       => '',
            'strRes'          => '',
            'mixedRes'        => [],
            'mixedResControl' => [],
            'strResControl'   => '',
            'strFrom'         => '',
            'strTo'           => '',
            'tCount'          => 0,
        ];

        #endregion Defaults
        $mixedSource = \alina\utils\Data::megaUnserialize($strSource);
        if (FALSE == $mixedSource) {
            return $data;
        }
        $typeSource = gettype($mixedSource);
        $mixedRes   = [];
        foreach ($mixedSource as $k => $v) {
            $iCount = 0;
            #region Some modification Staff here
            if ($flagRenameKeysAlso) {
                $k      = str_replace($strFrom, $strTo, $k, $iCount);
                $tCount += $iCount;
            }
            if (FALSE !== \alina\utils\Data::megaUnserialize($v)) {
                message::set('Source has SERIALIZED inside');
                $d = $this->serializedArraySearchReplace($v, $strFrom, $strTo, $tCount, $flagRenameKeysAlso);
                $v = $d->strResControl;

                // NO!!! We send local $tCount above by reference!!!
                //$tCount += $d->tCount;

            } elseif (Data::isIterable($v)) {
                message::set('Source has ITERABLE inside');
                $v = Data::itrSearchReplace($v, $strFrom, $strTo, $tCount, $flagRenameKeysAlso);
            } else {
                $v      = str_replace($strFrom, $strTo, $v, $iCount);
                $tCount += $iCount;
            }
            #endregion Some modification Staff here
            $mixedRes[$k] = $v;
        }
        settype($mixedRes, $typeSource);
        $strRes          = serialize($mixedRes);
        $mixedResControl = unserialize($strRes);
        $strResControl   = serialize($mixedResControl);

        $data = (object)[
            'strSource'       => $strSource,
            'strRes'          => $strRes,
            'mixedRes'        => $mixedRes,
            'mixedResControl' => $mixedResControl,
            'strResControl'   => $strResControl,
            'strFrom'         => $strFrom,
            'strTo'           => $strTo,
            'tCount'          => $tCount,
        ];

        return $data;
    }

    #####

    public function jsonSearchReplace($strJSON, $strFrom = '', $strTo = '')
    {
        #region Defaults
        $d = (object)[
            'strSource'            => $strJSON,
            'mxdJsonDecoded'       => [],
            'strRes'               => '',
            'mxdResJsonDecoded'    => [],
            'strFrom'              => $strFrom,
            'strTo'                => $strTo,
            'tCount'               => 0,
            'isSourceStrJsonValid' => TRUE,
            'isResStrJsonValid'    => TRUE,
        ];
        #endregion Defaults
        $d->isSourceStrJsonValid = \alina\utils\Data::isStringValidJson($d->strSource, $d->mxdJsonDecoded);
        #####
        if ($d->isSourceStrJsonValid) {
            $d->mxdResJsonDecoded = Data::itrSearchReplace($d->mxdJsonDecoded, $strFrom, $strTo, $d->tCount);
            $d->strRes            = json_encode($d->mxdResJsonDecoded);
            $d->isResStrJsonValid = \alina\utils\Data::isStringValidJson($d->strRes);
        }
        #####
        if (!$d->isSourceStrJsonValid) {
            message::set('Invalid SOURCE JSON string', [], 'alert alert-danger');
        }
        if (!$d->isResStrJsonValid) {
            message::set('Invalid RES JSON string', [], 'alert alert-danger');
        }

        return $d;
    }
}
