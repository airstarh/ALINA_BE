<?php

namespace alina\utils;

use alina\exceptionValidation;
use alina\Message;
use alina\MessageAdmin;
use Exception;
use HTMLPurifier;
use HTMLPurifier_Config;
use stdClass;

class Data
{
    /**
     * Checks if a $subject could be passed to foreach.
     * @param mixed $subject
     * @return bool
     */
    static public function isIterable($subject)
    {
        return (is_array($subject) || is_object($subject));
    }

    static public function toArray($v)
    {
        if (is_array($v)) {
            return $v;
        }
        if (static::isIterable($v)) {
            // ToDo: Make less heavy
            $array = json_decode(json_encode($v), TRUE);
        }
        else {
            $array = [$v];
        }

        return $array;
    }

    /**
     * @param $v
     * @return mixed
     * @throws Exception
     */
    static public function toObject($v)
    {
        if (!isset($v) || empty($v)) {
            return new \stdClass();
        }
        if (is_object($v)) {
            return $v;
        }
        if (is_array($v)) {
            // ToDo: Make less heavy
            return json_decode(json_encode($v), FALSE);
        }
        if (is_string($v)) {
            $res = json_decode($v);
            if (json_last_error() == JSON_ERROR_NONE) {
                return $res;
            }
        }

        //throw new \Exception('Unable to convert to object');
        return (object)[];
    }

    //@link https://stackoverflow.com/a/6041773/3142281
    static public function isStringValidJson($string, &$strJsonDecoded = NULL)
    {
        $strJsonDecoded = json_decode($string, FALSE, 512);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    ##################################################
    #region Search and replace
    static public function itrSearchReplace(&$itr, $strFrom, $strTo, &$tCount = 0, $flagRenameKeysAlso = FALSE)
    {
        /*
         * $itr is iterable value
         * */
        if (static::isIterable($itr)) {
            foreach ($itr as $k => &$v) {
                $iCount = 0;
                #####
                //ToDo: think on it or never use flagRenameKeysAlso :-)
                if ($flagRenameKeysAlso) {
                    $k      = str_replace($strFrom, $strTo, $k, $iCount);
                    $tCount += $iCount;
                }
                #####
                /**
                 * If Array or Ogject
                 */
                if (static::isIterable($v)) {
                    $v = static::itrSearchReplace($v, $strFrom, $strTo, $tCount, $flagRenameKeysAlso);
                }
                /**
                 * If Serialized string
                 */
                elseif (FALSE !== static::megaUnserialize($v, $itr2)) {
                    MessageAdmin::setInfo('Serialized inside JSON');
                    $vMid = static::itrSearchReplace($itr2, $strFrom, $strTo, $tCount, $flagRenameKeysAlso);
                    $v    = serialize($vMid);
                }
                /**
                 * If a string
                 */
                else {
                    $vTypeInitial = gettype($v);
                    $v            = str_replace($strFrom, $strTo, $v, $iCount);
                    settype($v, $vTypeInitial);
                    $tCount += $iCount;
                }
            }
        }
        /*
         * $itr is primitive
         * */
        else {
            $vTypeInitial = gettype($itr);
            $itr          = str_replace($strFrom, $strTo, $itr, $tCount);
            settype($itr, $vTypeInitial);
        }

        return $itr;
    }

    static public function serializedArraySearchReplace($strSource, $strFrom = '', $strTo = '', &$tCount = 0, $flagRenameKeysAlso = FALSE)
    {
        #region Defaults
        $data = (object)[
            'strSource'       => $strSource,
            'mixedSource'     => '',
            'strRes'          => '',
            'mixedRes'        => [],
            'mixedResControl' => [],
            'strResControl'   => '',
            'strFrom'         => $strFrom,
            'strTo'           => $strTo,
            'tCount'          => 0,
        ];
        #endregion Defaults
        $mixedSource     = static::megaUnserialize($strSource);
        $mixedSourceCopy = static::megaUnserialize($strSource);
        if (FALSE == $mixedSourceCopy) {
            Message::setDanger('Cannot unserialize data :-(');

            return $data;
        }
        $mixedRes = static::itrSearchReplace($mixedSourceCopy, $strFrom, $strTo, $tCount, $flagRenameKeysAlso);
        $strRes   = serialize($mixedRes);
        if (Str::ifContains($strRes, '__PHP_Incomplete_Class')) {
            Message::setDanger('Serialized result is incomplete!');
        }
        /*
         * Double-check if data is transformed correctly.
         */
        //$mixedResControl = unserialize($strRes);
        //$strResControl   = serialize($mixedResControl);
        $mixedResControl = [];
        $strResControl   = [];
        $data            = (object)[
            'strSource'       => $strSource,
            'mixedSource'     => $mixedSource,
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

    #endregion Search and replace
    ##################################################
    /**
     * Transforms input data to 'ASC' or 'DESC' string.
     * @param string|int|boolean $dir
     * @return string 'ASC' or 'DESC'
     */
    static public function getSqlDirection($dir)
    {
        if (is_string($dir)) {
            $dir = trim(strtoupper($dir));
            if ($dir === 'ASC' || $dir === 'DESC') {
                return $dir;
            }
        }
        $dir = filter_var($dir, FILTER_VALIDATE_BOOLEAN)
            ? 'ASC'
            : 'DESC';

        return $dir;
    }

    static public function utf8ize($d)
    {
        if (is_array($d) || is_object($d)) {
            foreach ($d as &$v) {
                $v = static::utf8ize($v);
            }
        }
        else {
            $enc   = mb_detect_encoding($d);
            $value = iconv($enc, 'UTF-8', $d);

            return $value;
        }

        return $d;
    }

    //ToDo: Less heavy. Validate input.
    static public function mergeObjects(...$objects)
    {
        $res = new stdClass();
        foreach ($objects as $o) {
            $res = (object)array_merge((array)$res, (array)$o);
        }

        return $res;
    }

    /**
     * Designed to completely remove WordPress problem
     * https://stackoverflow.com/questions/3148712/regex-code-to-fix-corrupt-serialized-php-data/55074706#55074706
     * @param string $str
     * @param NULL | string $resultOfUnserialization
     * @return bool|array
     */
    static public function megaUnserialize($str, &$resultOfUnserialization = NULL)
    {
        //ToDo: see later: https://stackoverflow.com/a/38708463/3142281
        #region Simple Security
        if (
            empty($str)
            || !is_string($str)
            || !preg_match('/^[aOs]:/', $str)
        ) {
            return FALSE;
        }
        $str = stripslashes($str);
        #endregion Simple Security
        ####################################################################################################
        try {
            ####################################################################################################
            #region SOLUTION 0
            // PHP default :-)
            $repSolNum               = 0;
            $strFixed                = $str;
            $resultOfUnserialization = @unserialize($strFixed);
            if (FALSE !== $resultOfUnserialization) {
                return $resultOfUnserialization;
            }
            #endregion SOLUTION 0
            ####################################################################################################
            #region SOLUTION 1
            // @link https://stackoverflow.com/a/5581004/3142281
            $repSolNum               = 1;
            $strFixed                = preg_replace_callback(
                '/s:([0-9]+):\"(.*?)\";/',
                function ($matches) { return "s:" . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
                $str
            );
            $resultOfUnserialization = @unserialize($strFixed);
            if (FALSE !== $resultOfUnserialization) {
                return $resultOfUnserialization;
            }
            #endregion SOLUTION 1
            ####################################################################################################
            #region SOLUTION 2
            // @link https://stackoverflow.com/a/24995701/3142281
            $repSolNum               = 2;
            $strFixed                = preg_replace_callback(
                '/s:([0-9]+):\"(.*?)\";/',
                function ($match) {
                    return "s:" . strlen($match[2]) . ':"' . $match[2] . '";';
                },
                $str);
            $resultOfUnserialization = @unserialize($strFixed);
            if (FALSE !== $resultOfUnserialization) {
                return $resultOfUnserialization;
            }
            #endregion SOLUTION 2
            ####################################################################################################
            #region SOLUTION 3
            // @link https://stackoverflow.com/a/34224433/3142281
            $repSolNum = 3;
            // securities
            $strFixed = preg_replace("%\n%", "", $str);
            // doublequote exploding
            $data     = preg_replace('%";%', "µµµ", $strFixed);
            $tab      = explode("µµµ", $data);
            $new_data = '';
            foreach ($tab as $line) {
                $new_data .= preg_replace_callback(
                    '%\bs:(\d+):"(.*)%',
                    function ($matches) {
                        $string       = $matches[2];
                        $right_length = strlen($string); // yes, strlen even for UTF-8 characters, PHP wants the mem size, not the char count

                        return 's:' . $right_length . ':"' . $string . '";';
                    },
                    $line);
            }
            $strFixed                = $new_data;
            $resultOfUnserialization = @unserialize($strFixed);
            if (FALSE !== $resultOfUnserialization) {
                return $resultOfUnserialization;
            }
            #endregion SOLUTION 3
            ####################################################################################################
            #region SOLUTION 4
            // @link https://stackoverflow.com/a/36454402/3142281
            $repSolNum               = 4;
            $strFixed                = preg_replace_callback(
                '/s:([0-9]+):"(.*?)";/',
                function ($match) {
                    return "s:" . strlen($match[2]) . ":\"" . $match[2] . "\";";
                },
                $str
            );
            $resultOfUnserialization = @unserialize($strFixed);
            if (FALSE !== $resultOfUnserialization) {
                return $resultOfUnserialization;
            }
            #endregion SOLUTION 4
            ####################################################################################################
            #region SOLUTION 5
            // @link https://stackoverflow.com/a/38890855/3142281
            $repSolNum               = 5;
            $strFixed                = preg_replace_callback('/s\:(\d+)\:\"(.*?)\";/s', function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; }, $str);
            $resultOfUnserialization = @unserialize($strFixed);
            if (FALSE !== $resultOfUnserialization) {
                return $resultOfUnserialization;
            }
            #endregion SOLUTION 5
            ####################################################################################################
            #region SOLUTION 6
            // @link https://stackoverflow.com/a/38891026/3142281
            $repSolNum = 6;
            $strFixed  = preg_replace_callback(
                '/s\:(\d+)\:\"(.*?)\";/s',
                function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
                $str);;
            $resultOfUnserialization = @unserialize($strFixed);
            if (FALSE !== $resultOfUnserialization) {
                return $resultOfUnserialization;
            }
            #endregion SOLUTION 6
            ####################################################################################################
        } catch (\ErrorException $e) {
            Message::setDanger($e->getMessage());

            return FALSE;
        }

        return FALSE;
    }

    static public function hlpGetBeautifulJsonString($d)
    {
        $s      = $d;
        $jflags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (is_array($d) || is_object($d)) {
            $s = json_encode($d, $jflags);
        }
        if (static::isStringValidJson($s, $res)) {
            return json_encode($res, $jflags);
        }
        else {
            return $s;
        }
    }

    static public function deleteEmptyProps($d)
    {
        $r = array_filter((array)$d);

        return is_array($d) ? (array)$r : (object)$r;
    }

    static public function isValidMd5($md5)
    {
        return strlen($md5) == 32 && ctype_xdigit($md5);
    }

    #####
    static public function stringify($data)
    {
        $res = json_encode($data, JSON_UNESCAPED_UNICODE);
        $res = str_replace('"', '', $res);
        $res = str_replace(',', ' | ', $res);
        $res = str_replace('{', '', $res);
        $res = str_replace('}', '', $res);

        return $res;
    }
    #####

    /**
     * @param $strJSON
     * @param string $strFrom
     * @param string $strTo
     * @return object
     */
    static public function jsonSearchReplace($strJSON, $strFrom = '', $strTo = '')
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
            Data::isStringValidJson($d->strSource, $d->mxdResJsonDecoded);
            $d->mxdResJsonDecoded = Data::itrSearchReplace($d->mxdResJsonDecoded, $strFrom, $strTo, $d->tCount);
            $d->strRes            = json_encode($d->mxdResJsonDecoded);
            $d->isResStrJsonValid = Data::isStringValidJson($d->strRes);
        }
        #####
        if (!$d->isSourceStrJsonValid) {
            AlinaResponseSuccess(0);
            Message::setDanger('Invalid SOURCE JSON string', []);
        }
        if (!$d->isResStrJsonValid) {
            AlinaResponseSuccess(0);
            Message::setDanger('Invalid RES JSON string', []);
        }

        return $d;
    }

    ##################################################
    #region Bulk Sanitize
    static protected $arrOutputDoNotTouch = [];
    static protected $arrOutputDoUnset    = [
        'password',
        'password_confirm',
        'confirm_password',
        'alinapath',
    ];

    static public function sanitizeOutputObj(&$object, $arrOutputDoNotTouch = NULL, $arrOutputDoUnset = NULL)
    {
        #####
        $arrOutputDoNotTouch = ($arrOutputDoNotTouch === NULL) ? static::$arrOutputDoNotTouch : $arrOutputDoNotTouch;
        $arrOutputDoUnset    = ($arrOutputDoUnset === NULL) ? static::$arrOutputDoUnset : $arrOutputDoUnset;
        #####
        foreach ($object as $f => &$v) {
            #####
            if (in_array($f, $arrOutputDoNotTouch)) {
                continue;
            }
            if (in_array($f, $arrOutputDoUnset)) {
                unset($object->{$f});
                continue;
            }
        }

        return $object;
    }

    ##################################################
    static protected $arrInputDoNotTouch = [
    ];
    static protected $arrInputDoUnset    = [];

    static public function sanitizeInputObj(&$object, $arrInputDoNotTouch = NULL, $arrInputDoUnset = NULL)
    {
        #####
        $arrInputDoNotTouch = ($arrInputDoNotTouch === NULL) ? static::$arrInputDoNotTouch : $arrInputDoNotTouch;
        $arrInputDoUnset    = ($arrInputDoUnset === NULL) ? static::$arrInputDoUnset : $arrInputDoUnset;
        #####
        foreach ($object as $f => &$v) {
            #####
            if (in_array($f, $arrInputDoNotTouch)) {
                continue;
            }
            #####
            if (is_string($object->{$f})) {
                $object->{$f} = trim($object->{$f});
            }
            #####
            if (in_array($f, $arrInputDoUnset)) {
                unset($object->{$f});
                continue;
            }
        }

        return $object;
    }

    #endregion Bulk Sanitize
    ##################################################
    ##################################################
    ##################################################
    #region Filter_Var
    static public function filterObject(stdClass &$data, array $filters)
    {
        foreach ($data as $fName => $fValue) {
            if (isset($filters[$fName]) && !empty($filters[$fName])) {
                foreach ($filters[$fName] as $filter) {
                    if (is_string($filter) && function_exists($filter)) {
                        $data->{$fName} = $filter($data->{$fName});
                    }
                    else {
                        if ($filter instanceof \Closure) {
                            $data->{$fName} = call_user_func($filter, $data->{$fName});;
                        }
                        else {
                            if (is_array($filter)) {
                                $argsAmount = count($filter);
                                switch ($argsAmount) {
                                    case 2:
                                        list($obj, $method) = $filter;
                                        $data->{$fName} = call_user_func([$obj, $method], $data->{$fName});
                                        break;
                                }
                            }
                        }
                    }
                    // ToDo: Maybe more abilities for filter.
                }
            }
        }
    }

    static public function filterVarBoolean($v)
    {
        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);

        return $v;
    }

    static public function filterVarInteger($v)
    {
        $v = filter_var($v, FILTER_SANITIZE_NUMBER_INT);

        return $v;
    }

    static public function filterVarFloat($v)
    {
        $v = filter_var(
            $v,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC
        );

        return $v;
    }

    static public function filterVarStrProperName($v)
    {
        $v = filter_var($v, FILTER_SANITIZE_STRING);

        return $v;
    }

    static public function filterVarStripTags($v)
    {
        $v = strip_tags($v);

        return $v;
    }

    static public function filterVarStrHtml($v)
    {
        #####
        if (empty($v)) {
            return '';
        }
        #####
        $forbidden = [
            '//style',
            '//script',
        ];
        #####
        $html = $v;
        ##################################################
        $HTML5DOMDocument                     = new \IvoPetkov\HTML5DOMDocument();
        $HTML5DOMDocument->preserveWhiteSpace = TRUE;
        $HTML5DOMDocument->formatOutput       = FALSE;
        $HTML5DOMDocument->loadHTML($html);
        ##################################################
        $DOMXpath = new \DOMXpath($HTML5DOMDocument);
        foreach ($DOMXpath->query(implode('|', $forbidden)) as $node) {
            $node->parentNode->removeChild($node);
        }
        ##################################################
        $body     = $HTML5DOMDocument->getElementsByTagName('body')->item(0);
        $bodyHTML = $body->innerHTML;

        return $bodyHTML;
    }
    #rendegion Filter_Var
    ##################################################
    #region Validate
    static public function validateObject(stdClass &$data, array $validators)
    {
        foreach ($data as $fName => $fValue) {
            if (isset($validators[$fName]) && !empty($validators[$fName])) {
                foreach ($validators[$fName] as $validator) {
                    $VALIDATION_RESULT = TRUE;
                    #####
                    if (is_array($validator) && array_key_exists('f', $validator)) {
                        $CHECKER = $validator['f'];
                    }
                    else if (is_string($validator) || is_bool($validator)) {
                        $CHECKER   = $validator;
                        $validator = [$validator];
                    }
                    else {
                        Message::setDanger("Undefined validator for {$fName}");
                        continue;
                    };
                    #####
                    $errorIf = (isset($validator['errorIf']))
                        ? $validator['errorIf']
                        : [FALSE, 0, '', NULL];
                    $msg     = (isset($validator['msg']) && !empty($validator['msg']))
                        ? $validator['msg']
                        : "Validation failed. Field:{$fName}. Value: {$fValue}";
                    #####
                    if (is_bool($CHECKER)) {
                        $VALIDATION_RESULT = $CHECKER;
                    }
                    else if (is_string($CHECKER) && function_exists($CHECKER)) {
                        $VALIDATION_RESULT = $CHECKER($fValue);
                    }
                    else if ($CHECKER instanceof \Closure) {
                        $VALIDATION_RESULT = call_user_func($CHECKER, $fValue);;
                    }
                    else if (is_array($CHECKER)) {
                        $countArgs = count($CHECKER);
                        switch ($countArgs) {
                            case 2:
                                list($class, $staticMethod) = $CHECKER;
                                $VALIDATION_RESULT = call_user_func([$class, $staticMethod], $fValue);
                                break;
                        }
                    }
                    // Validation Result process.
                    if (in_array($VALIDATION_RESULT, $errorIf, TRUE)) {
                        $message = "{$msg} (field:{$fName})";
                        Message::setDanger($message);
                        throw new exceptionValidation($message);
                    }
                }
            }
        }
    }

    #endregion Validate
    ##################################################
    #region Pagination
    static public function paginator($rowsTotal, $pageCurrentNumber, $pageSize, $versa = FALSE)
    {
        ##############################
        $pg = (object)[
            'limit'  => $pageSize,
            'offset' => NULL,
            'rows'   => $rowsTotal,
            'pages'  => NULL,
            'page'   => $pageCurrentNumber,
        ];
        ##############################
        #region Special Case All
        if ($pg->page === 'all') {
            $pg->limit  = $pg->rows;
            $pg->offset = 0;

            return $pg;
        }
        #endregion Special Case All
        ##############################
        #region Validation
        if (!isset($pg->limit) || empty($pg->limit) || $pg->limit <= 0) {
            $pg->limit = $pg->rows;
        }
        if ($pg->page !== 'last') {
            if ($pg->rows <= $pg->limit) {
                $pg->page = 1;
            }
            if (!isset($pg->page) || empty($pg->page) || $pg->page <= 0) {
                $pg->page = 1;
            }
        }
        #region Validation
        ##############################
        #region Pages Total
        if ($pg->rows <= 0) {
            $pg->pages = 1;
        }
        else {
            $pg->pages = ceil($pg->rows / $pg->limit);
        }
        if ($pg->page > $pg->pages || $pg->page === 'last') {
            $pg->page = $pg->pages;
        }
        #endregion Pages Total
        ##############################
        #region Offset
        if (!isset($pg->limit) || empty($pg->limit) || $pg->limit <= 0
            ||
            !isset($pg->page) || empty($pg->page) || $pg->page <= 0) {
            $pg->offset = 0;
        }
        else {
            $pg->offset = $pg->limit * ($pg->page - 1);
        }
        ##############################
        #region Special Case Versa Pagination (when the last page has full page size, the first page has rest)
        if ($versa) {
            $rest = $pg->rows % $pg->limit;
            if ($rest < $pg->limit) {
                $diff       = $pg->limit - $rest;
                $pg->offset = $pg->limit * ($pg->page - 1) - $diff;
                //ToDo: limit vs pageSize!!!
                // if ($pg->offset < 0) {
                //     $pg->offset = 0;
                //     $pg->limit  = $rest;
                // }
                $pg->rest = $rest;
                $pg->diff = $diff;
            }
        }
        #endregion Special Case Versa Pagination (when the last page has full page size, the first page has rest)
        ##############################
        #endregion Offset
        ##############################
        return $pg;
    }
    #endregion Pagination
    ##################################################
}
