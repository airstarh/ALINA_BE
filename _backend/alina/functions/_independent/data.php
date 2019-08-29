<?php

/**
 * Checks if a $subject could be passed to foreach.
 * @param mixed $subject
 * @return bool
 */
function isIterable($subject)
{
    return (is_array($subject) || is_object($subject));
}

function toArray($v)
{
    if (is_array($v)) {
        return $v;
    }

    if (isIterable($v)) {
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
function toObject($v)
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
function isStringValidJson($string)
{
    json_decode($string);

    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Transforms input data to 'ASC' or 'DESC' string.
 * @param string|int|boolean $dir
 * @return string 'ASC' or 'DESC'
 */
function getSqlDirection($dir)
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

function utf8ize($d)
{
    if (is_array($d) || is_object($d)) {
        foreach ($d as &$v) {
            $v = utf8ize($v);
        }
    } else {
        $enc   = mb_detect_encoding($d);
        $value = iconv($enc, 'UTF-8', $d);

        return $value;
    }

    return $d;
}

//ToDo: Less heavy. Validate input.
function mergeSimpleObjects(...$objects)
{
    $res = new stdClass();
    foreach ($objects as $o) {
        $res = (object)array_merge((array)$res, (array)$o);
    }

    return $res;
}

/**
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 */
/**
 * Tests if an input is valid PHP serialized string.
 *
 * Checks if a string is serialized using quick string manipulation
 * to throw out obviously incorrect strings. Unserialize is then run
 * on the string to perform the final verification.
 *
 * Valid serialized forms are the following:
 * <ul>
 * <li>boolean: <code>b:1;</code></li>
 * <li>integer: <code>i:1;</code></li>
 * <li>double: <code>d:0.2;</code></li>
 * <li>string: <code>s:4:"test";</code></li>
 * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
 * <li>object: <code>O:8:"stdClass":0:{}</code></li>
 * <li>null: <code>N;</code></li>
 * </ul>
 *
 * @param string $value Value to test for serialized form
 * @param mixed $result Result of unserialize() of the $value
 * @return        boolean            True if $value is serialized data, otherwise false
 * @author        Chris Smith <code+php@chris.cs278.org>
 * @copyright    Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
 * @license        http://sam.zoy.org/wtfpl/ WTFPL
 * @deprecated
 */
function is_serialized($value, &$result = NULL)
{
    // Bit of a give away this one
    if (!is_string($value)) {
        return FALSE;
    }

    $value = normalizeSerializedString($value);

    // Serialized false, return true. unserialize() returns false on an
    // invalid string or it could return false if the string is serialized
    // false, eliminate that possibility.
    if ($value === 'b:0;') {
        $result = FALSE;

        return TRUE;
    }
    $length = strlen($value);
    $end    = '';
    switch ($value[0]) {
        case 's':
            if ($value[$length - 2] !== '"') {
                return FALSE;
            }
            break;
        case 'b':
        case 'i':
        case 'd':
            // This looks odd but it is quicker than isset()ing
            $end .= ';';
            break;
        case 'a':
        case 'O':
            $end .= '}';
            if ($value[1] !== ':') {
                return FALSE;
            }
            switch ($value[2]) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                    break;
                default:
                    return FALSE;
            }
            break;
        case 'N':
            $end .= ';';
            if ($value[$length - 1] !== $end[0]) {
                return FALSE;
            }
            break;
        default:
            return FALSE;
    }
    if (($result = @unserialize($value)) === FALSE) {
        $result = NULL;

        return FALSE;
    }

    return TRUE;
}

function normalizeSerializedString($str) { return $str; }

/**
 * Designed to completely remove WordPress problem
 * https://stackoverflow.com/questions/3148712/regex-code-to-fix-corrupt-serialized-php-data/55074706#55074706
 * @param string $str
 * @return bool|array
 */
function hlpSuperUnSerialize($str)
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

