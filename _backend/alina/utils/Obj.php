<?php

namespace alina\utils;

class Obj
{
    static public function getValByPropNameCaseInsensitive($pName, $obj)
    {
        $arr          = (array)$obj;
        $arrPropNames = array_keys($arr);
        foreach ($arrPropNames as $name) {
            if (strtolower($name) === strtolower($pName)) {
                return $obj->{$name};
                break;
            }
        }

        return NULL;
    }
}
