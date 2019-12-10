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

        return null;
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
