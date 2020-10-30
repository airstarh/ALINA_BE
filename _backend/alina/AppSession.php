<?php
//ToDo: SIMPLIFY IT!!!
namespace alina;
class AppSession
{
    static public $storage              = [];
    static public $flagSessionInStorage = FALSE;

    static public function set($path, $value)
    {
        static::start();

        return \alina\utils\Arr::setArrayValue($path, $value, static::$storage);
    }

    static public function get($path = NULL)
    {
        static::start();
        if (empty($path)) {
            return static::$storage;
        }

        return \alina\utils\Arr::getArrayValue($path, static::$storage);
    }

    static public function delete($path)
    {
        static::start();

        return \alina\utils\Arr::unsetArrayPath($path, static::$storage);
    }

    static public function has($path)
    {
        static::start();

        return \alina\utils\Arr::arrayHasPath($path, static::$storage);
    }

    static public function start()
    {
        if (!headers_sent()) {
            if (!static::isStarted()) {
                if (PHP_VERSION_ID >= 70300) {
                    $cookieParams             = session_get_cookie_params();
                    $cookieParams['SameSite'] = "None";
                    $cookieParams['secure']   = TRUE;
                    session_set_cookie_params($cookieParams);
                }
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
