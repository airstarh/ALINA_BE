<?php
function getArrayValueByPath(array $path = [], array $array)
{
    $cfg = &$array;
    foreach ($path as $section) {
        if (isset($cfg[$section])) {
            $cfg = &$cfg[$section];
        }
        else {
            throw new \Exception("No section $section in Array");
        }
    }
    return $cfg;
}

function getArrayValueByStringPath($path, array $array, $delimiter = '/')
{
    $path = explode($delimiter, $path);
    return getArrayValueByPath($path, $array);
}

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