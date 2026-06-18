<?php

namespace alina\Utils;

class Arr
{
    #region Getters & Setters
    #region Getters
    public static function getArrayValue($path, array $array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::getArrayValueByArrayPath($path, $array);
        }

        return static::getArrayValueByStringPath($path, $array, $delimiter);
    }

    public static function getArrayValueByArrayPath(array $path, array $array)
    {
        $temp = &$array;

        foreach ($path as $section) {
            if (array_key_exists($section, $temp)) {
                $temp = &$temp[$section];
            }
            else {
                return null;
            }
        }

        return $temp;
    }

    public static function getArrayValueByStringPath($path, array $array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::getArrayValueByArrayPath($path, $array);
    }

    #endregion Getters
    #region Setters
    public static function setArrayValue($path, $value, array &$array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::setArrayValueByArrayPath($path, $value, $array);
        }

        return static::setArrayValueByStringPath($path, $value, $array, $delimiter);
    }

    public static function setArrayValueByArrayPath(array $path, $value, array &$array)
    {
        $temp = &$array;

        foreach ($path as $p) {
            $temp = &$temp[$p];
        }
        $temp = $value;

        return true;
    }

    public static function setArrayValueByStringPath($path, $value, array &$array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::setArrayValueByArrayPath($path, $value, $array);
    }

    #endregion Setters
    #region Path checker
    public static function arrayHasPath($path, array $array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::checkArrayPathByArray($path, $array);
        }
        else {
            return static::checkArrayPathByString($path, $array, $delimiter);
        }
    }

    public static function checkArrayPathByArray(array $path, array $array, &$value = null)
    {
        $temp = &$array;

        foreach ($path as $p) {
            if (array_key_exists($p, $temp)) {
                $temp = &$temp[$p];
            }
            else {
                return false;
            }
        }
        $value = $temp;

        return true;
    }

    public static function checkArrayPathByString($path, array $array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::checkArrayPathByArray($path, $array);
    }

    #endregion Path checker
    #region Unsetter
    public static function unsetArrayPath($path, array &$array, $delimiter = '/')
    {
        if (is_array($path)) {
            return static::unsetArrayPathByArrayPath($path, $array);
        }

        return static::unsetArrayPathByStringPath($path, $array, $delimiter);
    }

    public static function unsetArrayPathByArrayPath(array $path, array &$array)
    {
        $previousElement = null;
        $temp            = &$array;

        foreach ($path as &$p) {
            $previousElement = &$temp;
            $temp            = &$temp[$p];
        }

        if ($previousElement !== null && isset($p)) {
            unset($previousElement[$p]);
        }

        return $array;
    }

    public static function unsetArrayPathByStringPath($path, array &$array, $delimiter = '/')
    {
        $path = explode($delimiter, $path);

        return static::unsetArrayPathByArrayPath($path, $array);
    }

    #endregion Unsetter
    #endregion Getters & Setters
    public static function firstArrayKey($array)
    {
        reset($array);
        [$key, $value] = each($array);

        return $key;
    }

    public static function firstArrayValue($array)
    {
        reset($array);
        [$key, $value] = each($array);

        return $value;
    }

    public static function lastArrayKey($array)
    {
        $arrayOfKeys = array_keys($array);

        return end($arrayOfKeys);
    }

    public static function lastArrayValue($array)
    {
        return end($array);
    }

    public static function arrayMergeRecursive(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]) && ! is_numeric($key)) {
                $merged[$key] = static::arrayMergeRecursive($merged[$key], $value);
            }
            else {
                if (is_numeric($key)) {
                    if (! in_array($value, $merged)) {
                        $merged[] = $value;
                    }
                }
                else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }
}
