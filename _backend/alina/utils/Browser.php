<?php

namespace alina\utils;

class Browser
{
    static public function hash($userAgent)
    {
        return md5($userAgent);
    }
}
