<?php

namespace alina;

use alina\traits\Singleton;
use alina\utils\Arr;

class GlobalRequestStorage
{
    #region Singleton
    use Singleton;

    protected function __construct() { }
    #endregion Singleton
    #region CRUD
    protected $memory = [];

    static public function set($prop, $val)
    {
        Arr::setArrayValue($prop, $val, static::obj()->memory);

        return $val;
    }

    static public function setPlus1($prop)
    {
        $count = static::get($prop);
        if (empty($count)) {
            $count = 0;
        }
        $count++;
        static::set($prop, $count);

        return $count;
    }

    static public function get($prop)
    {
        if (Arr::arrayHasPath($prop, static::obj()->memory)) {
            return Arr::getArrayValue($prop, static::obj()->memory);
        }

        return NULL;
    }

    static public function getAll()
    {
        return static::obj()->memory;
    }
    #endregion CRUD
}
