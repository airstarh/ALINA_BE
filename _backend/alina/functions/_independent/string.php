<?php

function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (mb_substr($haystack, 0, $length) === (string)$needle);
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (mb_substr($haystack, -$length) === (string)$needle);
}