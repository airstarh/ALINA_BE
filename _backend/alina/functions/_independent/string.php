<?php

function startsWith($haystack, $needle)
{
    $length = mb_strlen($needle);

    return (mb_substr($haystack, 0, $length) === (string)$needle);
}

function endsWith($haystack, $needle)
{
    $length = mb_strlen($needle);
    if ($length == 0) {
        return TRUE;
    }

    return (mb_substr($haystack, -$length) === (string)$needle);
}

function contains($haystack, $needle)
{
    return mb_stripos($haystack, $needle);
}

