<?php

namespace alina\mvc\model;

use alina\message;
use alina\utils\Data;

class DataPlayer
{
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
