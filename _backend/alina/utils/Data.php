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
        } else {
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
        throw new \Exception('Alina cannot convert to object: ' . var_export($v, 1));
        //return $object;
    }

    //@link https://stackoverflow.com/a/6041773/3142281
    static public function isStringValidJson($string, &$strJsonDecoded = NULL)
    {
        $strJsonDecoded = json_decode($string, FALSE, 512);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    ##################################################
    #region Search and replace
    static public function itrSearchReplace($itr, $strFrom, $strTo, &$tCount = 0, $flagRenameKeysAlso = FALSE)
    {
        $res     = [];
        $itrType = gettype($itr);;
        if (static::isIterable($itr)) {
            foreach ($itr as $k => $v) {
                $iCount = 0;
                if ($flagRenameKeysAlso) {
                    $k      = str_replace($strFrom, $strTo, $k, $iCount);
                    $tCount += $iCount;
                }
                if (static::isIterable($v)) {
                    $v = static::itrSearchReplace($v, $strFrom, $strTo, $tCount);
                } elseif (FALSE !== static::megaUnserialize($v)) {
                    MessageAdmin::set('Serialized inside JSON');
                    $d = static::serializedArraySearchReplace($v, $strFrom, $strTo, $tCount, $flagRenameKeysAlso);
                    $v = $d->strResControl;
                } else {
                    $vTypeInitial = gettype($v);
                    $v            = str_replace($strFrom, $strTo, $v, $iCount);
                    #####
                    #region Care of types
                    //ToDo: Does not work with eg number -> float
                    $vRes1   = $v;
                    $vRes2   = $v;
                    $success = settype($vRes1, $vTypeInitial);
                    if ($success && (string)$vRes1 == (string)$vRes2) {
                        settype($v, $vTypeInitial);
                    }
                    #endregion Care of types
                    #####
                    $tCount += $iCount;
                }
                $res[$k] = $v;
            }
            settype($res, $itrType);
        } else {
            $res = str_replace($strFrom, $strTo, $itr, $tCount);
        }

        return $res;
    }

    static public function serializedArraySearchReplace($strSource, $strFrom = '', $strTo = '', &$tCount = 0, $flagRenameKeysAlso = FALSE)
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
        $mixedSource = static::megaUnserialize($strSource);
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
            if (FALSE !== static::megaUnserialize($v)) {
                MessageAdmin::set('Source has SERIALIZED inside');
                $d = static::serializedArraySearchReplace($v, $strFrom, $strTo, $tCount, $flagRenameKeysAlso);
                $v = $d->strResControl;
                // NO!!! We send local $tCount above by reference!!!
                //$tCount += $d->tCount;
            } elseif (Data::isIterable($v)) {
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
        $data            = (object)[
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
        } else {
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
     * @param null $unserialized (ToDo...)
     * @return bool|array
     */
    static public function megaUnserialize($str, &$unserialized = NULL)
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
        #region SOLUTION 0
        // PHP default :-)
        $repSolNum = 0;
        $strFixed  = $str;
        $arr       = @unserialize($strFixed);
        if (FALSE !== $arr) {
            alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

            return $arr;
        }
        #endregion SOLUTION 0
        #region SOLUTION 1
        // @link https://stackoverflow.com/a/5581004/3142281
        $repSolNum = 1;
        $strFixed  = preg_replace_callback(
            '/s:([0-9]+):\"(.*?)\";/',
            function ($matches) { return "s:" . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
            $str
        );
        $arr       = @unserialize($strFixed);
        if (FALSE !== $arr) {
            alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

            return $arr;
        }
        #endregion SOLUTION 1
        #region SOLUTION 2
        // @link https://stackoverflow.com/a/24995701/3142281
        $repSolNum = 2;
        $strFixed  = preg_replace_callback(
            '/s:([0-9]+):\"(.*?)\";/',
            function ($match) {
                return "s:" . strlen($match[2]) . ':"' . $match[2] . '";';
            },
            $str);
        $arr       = @unserialize($strFixed);
        if (FALSE !== $arr) {
            alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

            return $arr;
        }
        #endregion SOLUTION 2
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
        $strFixed = $new_data;
        $arr      = @unserialize($strFixed);
        if (FALSE !== $arr) {
            alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

            return $arr;
        }
        #endregion SOLUTION 3
        #region SOLUTION 4
        // @link https://stackoverflow.com/a/36454402/3142281
        $repSolNum = 4;
        $strFixed  = preg_replace_callback(
            '/s:([0-9]+):"(.*?)";/',
            function ($match) {
                return "s:" . strlen($match[2]) . ":\"" . $match[2] . "\";";
            },
            $str
        );
        $arr       = @unserialize($strFixed);
        if (FALSE !== $arr) {
            alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

            return $arr;
        }
        #endregion SOLUTION 4
        #region SOLUTION 5
        // @link https://stackoverflow.com/a/38890855/3142281
        $repSolNum = 5;
        $strFixed  = preg_replace_callback('/s\:(\d+)\:\"(.*?)\";/s', function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; }, $str);
        $arr       = @unserialize($strFixed);
        if (FALSE !== $arr) {
            alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

            return $arr;
        }
        #endregion SOLUTION 5
        #region SOLUTION 6
        // @link https://stackoverflow.com/a/38891026/3142281
        $repSolNum = 6;
        $strFixed  = preg_replace_callback(
            '/s\:(\d+)\:\"(.*?)\";/s',
            function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
            $str);;
        $arr = @unserialize($strFixed);
        if (FALSE !== $arr) {
            alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

            return $arr;
        }
        #endregion SOLUTION 6
        alinaErrorLog('Completely unable to deserialize.');

        return FALSE;
    }

    static public function hlpGetBeautifulJsonString($d)
    {
        $s = $d;
        if (is_array($d) || is_object($d)) {
            $s = json_encode($d);
        }
        if (static::isStringValidJson($s, $res)) {
            return json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
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
                    } else {
                        if ($filter instanceof \Closure) {
                            $data->{$fName} = call_user_func($filter, $data->{$fName});;
                        } else {
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

    static public function filterVarStrHtml($v)
    {
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
                    } else if (is_string($validator) || is_bool($validator)) {
                        $CHECKER   = $validator;
                        $validator = [$validator];
                    } else {
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
                    } else if (is_string($CHECKER) && function_exists($CHECKER)) {
                        $VALIDATION_RESULT = $CHECKER($fValue);
                    } else if ($CHECKER instanceof \Closure) {
                        $VALIDATION_RESULT = call_user_func($CHECKER, $fValue);;
                    } else if (is_array($CHECKER)) {
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
    static public function paginator($rowsTotal, $pageCurrentNumber, $pageSize)
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
        #region Validation
        if (!isset($pg->limit) || empty($pg->limit) || $pg->limit <= 0) {
            $pg->limit = $pg->rows;
        }
        if ($pg->rows <= $pg->limit) {
            $pg->page = 1;
        }
        if (!isset($pg->page) || empty($pg->page) || $pg->page <= 0) {
            $pg->page = 1;
        }
        #region Validation
        ##############################
        #region Pages Total
        $pg->pages = ceil($pg->rows / $pg->limit);
        if ($pg->page > $pg->pages) {
            $pg->page = $pg->pages;
        }
        #endregion Pages Total
        ##############################
        #region Offset
        if (!isset($pg->limit) || empty($pg->limit) || $pg->limit <= 0
            ||
            !isset($pg->page) || empty($pg->page) || $pg->page <= 0) {
            $pg->offset = 0;
        } else {
            $pg->offset = $pg->limit * ($pg->page - 1);
        }
        #endregion Offset
        ##############################
        return $pg;
    }
    #endregion Pagination
    ##################################################
}
