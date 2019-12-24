<?php

namespace alina\utils;

class Arr
{
    #region Getters & Setters
#region Getters
    static public function getArrayValue($path, array $array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::getArrayValueByArrayPath($path, $array);
        }

        return static::getArrayValueByStringPath($path, $array, $delimiter);
    }

    static public function getArrayValueByArrayPath(array $path, array $array)
    {
        $temp = &$array;
        foreach ($path as $section) {
            if (array_key_exists($section, $temp)) {
                $temp = &$temp[$section];
            } else {
                return NULL;
            }
        }

        return $temp;
    }

    static public function getArrayValueByStringPath($path, array $array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::getArrayValueByArrayPath($path, $array);
    }

#endregion Getters

#region Setters
    static public function setArrayValue($path, $value, array &$array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::setArrayValueByArrayPath($path, $value, $array);
        }

        return static::setArrayValueByStringPath($path, $value, $array, $delimiter);
    }

    static public function setArrayValueByArrayPath(array $path, $value, array &$array)
    {
        $temp = &$array;
        foreach ($path as $p) {
            $temp = &$temp[$p];
        }
        $temp = $value;

        return TRUE;
    }

    static public function setArrayValueByStringPath($path, $value, array &$array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::setArrayValueByArrayPath($path, $value, $array);
    }

#endregion Setters

#region Path checker
    static public function arrayHasPath($path, array $array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::checkArrayPathByArray($path, $array);
        } else {
            return static::checkArrayPathByString($path, $array, $delimiter);
        }
    }

    static public function checkArrayPathByArray(array $path, array $array, &$value = NULL)
    {
        $temp = &$array;
        foreach ($path as $p) {
            if (array_key_exists($p, $temp)) {
                $temp = &$temp[$p];
            } else {
                return FALSE;
            }
        }

        $value = $temp;

        return TRUE;
    }

    static public function checkArrayPathByString($path, array $array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::checkArrayPathByArray($path, $array);
    }

#endregion Path checker

#region Unsetter
    static public function unsetArrayPath($path, array &$array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::unsetArrayPathByArrayPath($path, $array);
        }

        return static::unsetArrayPathByStringPath($path, $array, $delimiter);
    }

    static public function unsetArrayPathByArrayPath(array $path, array &$array)
    {
        $previousElement = NULL;
        $temp            = &$array;
        foreach ($path as &$p) {
            $previousElement = &$temp;
            $temp            = &$temp[$p];
        }
        if ($previousElement !== NULL && isset($p)) {
            unset($previousElement[$p]);
        }

        return $array;
    }

    static public function unsetArrayPathByStringPath($path, array &$array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::unsetArrayPathByArrayPath($path, $array);
    }

#endregion Unsetter

#endregion Getters & Setters
    static public function firstArrayKey($array)
    {
        reset($array);
        list($key, $value) = each($array);

        return $key;
    }

    static public function firstArrayValue($array)
    {
        reset($array);
        list($key, $value) = each($array);

        return $value;
    }

    static public function lastArrayKey($array)
    {
        $arrayOfKeys = array_keys($array);

        return end($arrayOfKeys);
    }

    static public function lastArrayValue($array)
    {
        return end($array);
    }

    static public function arrayMergeRecursive(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]) && !is_numeric($key)) {
                $merged[$key] = static::arrayMergeRecursive($merged[$key], $value);
            } else {
                if (is_numeric($key)) {
                    if (!in_array($value, $merged)) {
                        $merged[] = $value;
                    }
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }
}
