<?php
//ToDo: SIMPLIFY IT!!!

namespace alina;

class session
{

    static public $storage              = [];
    static public $flagSessionInStorage = FALSE;

    static public function set($path, $value)
    {
        static::start();

        return setArrayValue($path, $value, static::$storage);
    }

    static public function get($path)
    {
        static::start();

        return getArrayValue($path, static::$storage);
    }

    static public function delete($path)
    {
        static::start();

        return unsetArrayPath($path, static::$storage);
    }

    static public function has($path)
    {
        static::start();

        return arrayHasPath($path, static::$storage);
    }

    static public function start()
    {
        if (!headers_sent()) {
            if (!static::isStarted()) {
                session_start();
            }
        }

        if (static::isStarted()) {
            if (!static::$flagSessionInStorage) {
                static::$storage              =& $_SESSION;
                static::$flagSessionInStorage = TRUE;
            }
        }
    }

    static public function stop()
    {
        //ToDo: May be safe session deletion, when it is necessary just pause it.
        //ToDo: Should I delete static::storage ?
        if (static::isStarted())
            session_destroy();
    }

    static public function isStarted()
    {
        $sessionId = session_id();

        return !empty($sessionId);
    }
}