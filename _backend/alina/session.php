<?php

namespace alina;

class session
{

    static public $storage = [];

    static public function vault()
    {
        static::start();
        if (static::isStarted()) {
            static::$storage =& $_SESSION;
        }

        return static::$storage;
    }

    static public function set($path, $value)
    {
        static::start();

        return setArrayValue($path, $value, static::vault());
    }

    static public function get($path)
    {
        static::start();

        return getArrayValue($path, static::vault());
    }

    static public function delete($path)
    {
        static::start();

        return unsetArrayPath($path, static::vault());
    }

    static public function has($path)
    {
        static::start();

        return arrayHasPath($path, static::vault());
    }

    static public function start()
    {
        if (!headers_sent()) {
            if (!static::isStarted()) {
                session_start();
            }
        }
    }

    static public function stop()
    {
        //ToDo: May be safe session deletion, when it is necessary just pause it.
        if (static::isStarted())
            session_destroy();
    }

    static public function isStarted()
    {
        $sessionId = session_id();

        return !empty($sessionId);
    }
}