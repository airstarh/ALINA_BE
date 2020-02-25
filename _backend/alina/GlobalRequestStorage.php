<?php

namespace alina;

use alina\traits\Singleton;

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
        static::obj()->memory[$prop] = $val;

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
        if (isset(static::obj()->memory[$prop])) {
            return static::obj()->memory[$prop];
        }

        return NULL;
    }

    static public function getAll()
    {
        return static::obj()->memory;
    }
    #endregion CRUD
}
