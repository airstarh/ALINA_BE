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
