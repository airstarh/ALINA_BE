<?php

function hlpStrStartsWith($haystack, $needle)
{
    $length = mb_strlen($needle);

    return (mb_substr($haystack, 0, $length) === (string)$needle);
}

function hlpStrEndsWith($haystack, $needle)
{
    $length = mb_strlen($needle);
    if ($length == 0) {
        return TRUE;
    }

    return (mb_substr($haystack, -$length) === (string)$needle);
}

function hlpStrContains($haystack, $needle)
{
    return mb_stripos($haystack, $needle);
}

