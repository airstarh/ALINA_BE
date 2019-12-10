<?php

namespace alina\traits;

trait Singleton
{
    private static $inst = NULL;

    /**
     * @return static
     */
    public static function obj()
    {
        if (!(static::$inst instanceof static)) {
            static::$inst = new static;
        }

        return static::$inst;
    }
}
