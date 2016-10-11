<?php
#region Getters & Setters
#region Getters
function getArrayValue($path, array $array, $delimiter = '/')
{
    if (is_array($path)) {
        return getArrayValueByArrayPath($path, $array);
    }

    return getArrayValueByStringPath($path, $array, $delimiter);
}

function getArrayValueByArrayPath(array $path = [], array $array)
{
    $temp = &$array;
    foreach ($path as $section) {
        if (array_key_exists($section, $temp)) {
            $temp = &$temp[$section];
        }
        else {
            throw new \Exception("No section $section in Array");
        }
    }

    return $temp;
}

function getArrayValueByStringPath($path, array $array, $delimiter = '/')
{
    $path = explode($delimiter, $path);

    return getArrayValueByArrayPath($path, $array);
}

#endregion Getters

#region Setters
function setArrayValue($path, $value, array &$array, $delimiter = '/')
{
    if (is_array($path)) {
        return setArrayValueByArrayPath($path, $value, $array);
    }

    return setArrayValueByStringPath($path, $value, $array, $delimiter);
}

function setArrayValueByArrayPath(array $path, $value, array &$array)
{
    $temp = &$array;
    foreach ($path as $p) {
        $temp = &$temp[$p];
    }
    $temp = $value;

    return TRUE;
}

function setArrayValueByStringPath($path, $value, array &$array, $delimiter = '/')
{
    $path = explode($delimiter, $path);

    return setArrayValueByArrayPath($path, $value, $array);
}

#endregion Setters

#region Path checker
function arrayHasPath($path, array $array, $delimiter = '/')
{
    if (is_array($path)) {
        return checkArrayPathByArray($path, $array);
    }
    else return checkArrayPathByString($path, $array, $delimiter);
}

function checkArrayPathByArray(array $path, array $array)
{
    $temp = &$array;
    foreach ($path as $p) {
        if (array_key_exists($p, $temp)) {
            $temp = &$temp[$p];
        }
        else
            return FALSE;
    }

    return TRUE;
}

function checkArrayPathByString($path, array $array, $delimiter = '/')
{
    $path = explode($delimiter, $path);

    return checkArrayPathByArray($path, $array);
}

#endregion Path checker

#region Unsetter
function unsetArrayPath($path, array &$array, $delimiter = '/')
{
    if (is_array($path)) {
        return unsetArrayPathByArrayPath($path, $array);
    }

    return unsetArrayPathByStringPath($path, $array, $delimiter);
}

function unsetArrayPathByArrayPath(array $path, array &$array)
{
    $previousElement = NULL;
    $temp            = &$array;
    foreach ($path as &$p) {
        $previousElement = &$temp;
        $temp            = &$temp[$p];
    }
    if ($previousElement !== NULL && isset($p))
        unset($previousElement[$p]);

    return $array;
}

function unsetArrayPathByStringPath($path, array &$array, $delimiter = '/')
{
    $path = explode($delimiter, $path);

    return unsetArrayPathByArrayPath($path, $array);
}

#endregion Unsetter

#endregion Getters & Setters
function firstArrayKey($array)
{
    reset($array);
    list($key, $value) = each($array);

    return $key;
}

function firstArrayValue($array)
{
    reset($array);
    list($key, $value) = each($array);

    return $value;
}

function arrayMergeRecursive(array $array1, array $array2)
{
    $merged = $array1;

    foreach ($array2 as $key => & $value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = arrayMergeRecursive($merged[$key], $value);
        }
        else if (is_numeric($key)) {
            if (!in_array($value, $merged))
                $merged[] = $value;
        }
        else
            $merged[$key] = $value;
    }

    return $merged;
}