<?php

namespace alina\Utils;

class Browser
{
    public static function hash($userAgent)
    {
        return md5($userAgent);
    }
}
