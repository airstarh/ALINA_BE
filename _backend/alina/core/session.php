<?php

namespace alina\core;

class session
{

    static public function set($path, $value)
    {
        static::start();
        setArrayValue($path, $value, $_SESSION);
    }

    static public function get($path)
    {
        static::start();
        return getArrayValue($path, $_SESSION);
    }

    static public function start()
    {
        if (!headers_sent()) {
            if (!static::isStarted())
                session_start();
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