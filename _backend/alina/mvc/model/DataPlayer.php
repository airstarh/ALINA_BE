<?php

namespace alina\mvc\model;

use alina\Message;
use alina\MessageAdmin;
use alina\utils\Data;

class DataPlayer
{
    /**
     * @param $strJSON
     * @param string $strFrom
     * @param string $strTo
     * @return object
     */
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
        $d->isSourceStrJsonValid = Data::isStringValidJson($d->strSource, $d->mxdJsonDecoded);
        #####
        if ($d->isSourceStrJsonValid) {
            $d->mxdResJsonDecoded = Data::itrSearchReplace($d->mxdJsonDecoded, $strFrom, $strTo, $d->tCount);
            $d->strRes            = json_encode($d->mxdResJsonDecoded);
            $d->isResStrJsonValid = Data::isStringValidJson($d->strRes);
        }
        #####
        if (!$d->isSourceStrJsonValid) {
            MessageAdmin::set('Invalid SOURCE JSON string', [], 'alert alert-danger');
        }
        if (!$d->isResStrJsonValid) {
            MessageAdmin::set('Invalid RES JSON string', [], 'alert alert-danger');
        }

        return $d;
    }
}
