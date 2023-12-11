<?php

namespace alina\Utils;

class Browser
{
    static public function hash($userAgent)
    {
        return md5($userAgent);
    }
}
