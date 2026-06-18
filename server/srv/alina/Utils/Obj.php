<?php

namespace alina\Utils;

class Obj
{
    public static function getValByPropNameCaseInsensitive($pName, $obj)
    {
        $arr          = (array)$obj;
        $arrPropNames = array_keys($arr);

        foreach ($arrPropNames as $name) {
            if (strtolower($name) === strtolower($pName)) {
                return $obj->{$name};

                break;
            }
        }

        return null;
    }

    public static function deepClone($obj)
    {
        $res = unserialize(serialize($obj));

        return $res;
    }
}
