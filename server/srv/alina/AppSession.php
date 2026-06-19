<?php

//ToDo: SIMPLIFY IT!!!

namespace alina;

class AppSession
{
    public static $storage              = [];
    public static $flagSessionInStorage = false;

    public static function set($path, $value)
    {
        static::start();

        return Utils\Arr::setArrayValue($path, $value, static::$storage);
    }

    public static function get($path = null)
    {
        static::start();

        if (empty($path)) {
            return static::$storage;
        }

        return Utils\Arr::getArrayValue($path, static::$storage);
    }

    public static function delete($path)
    {
        static::start();

        return Utils\Arr::unsetArrayPath($path, static::$storage);
    }

    public static function has($path)
    {
        static::start();

        return Utils\Arr::arrayHasPath($path, static::$storage);
    }

    public static function start()
    {
        if (! headers_sent()) {
            if (! static::isStarted()) {
                if (PHP_VERSION_ID >= 70300) {
                    $cookieParams             = session_get_cookie_params();
                    $cookieParams['SameSite'] = "None";
                    $cookieParams['secure']   = true;
                    session_set_cookie_params($cookieParams);
                }
                session_start();
            }
        }

        if (static::isStarted()) {
            if (! static::$flagSessionInStorage) {
                static::$storage              = &$_SESSION;
                static::$flagSessionInStorage = true;
            }
        }
    }

    public static function stop()
    {
        //ToDo: May be safe session deletion, when it is necessary just pause it.
        //ToDo: Should I delete static::storage ?
        if (static::isStarted()) {
            session_destroy();
        }
    }

    public static function isStarted()
    {
        $sessionId = session_id();

        return ! empty($sessionId);
    }
}
