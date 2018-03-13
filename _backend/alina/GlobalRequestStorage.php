<?php

namespace alina;

class GlobalRequestStorage
{
    #region Singleton
    static protected $instance = NULL;

    protected function __construct() { }

    /**
     * @return static
     */
    static public function obj()
    {
        if (
            empty(static::$instance)
            ||
            !is_a(static::$instance, get_class())
        ) {
            static::$instance = new static();
        }

        return static::$instance;
    }
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