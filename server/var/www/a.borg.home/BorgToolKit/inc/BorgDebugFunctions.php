<?php

require_once __DIR__ . '/BorgDebug.php';

if (!function_exists('fff')) {
    function fff($data)
    {
        BorgDebug::fDebug($data, FILE_APPEND, null, null);
    }
}

if (!function_exists('ffff')) {
    function ffff($data)
    {
        BorgDebug::fDebug($data, FILE_APPEND, null, 'json');
    }
}

if (!function_exists('ff')) {
    function ff($data)
    {
        fff($data);
        ffff($data);
    }
}

if (!function_exists('ffd')) {
    function ffd($data)
    {
        BorgDebug::dDebug($data);
    }
}