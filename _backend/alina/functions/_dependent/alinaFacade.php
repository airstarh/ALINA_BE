<?php
function Alina()
{
    return \alina\app::get();
}

function AlinaCFG($path)
{
    return \alina\app::getConfig($path);
}

function AlinaCurrentUserId()
{
    $u = [
        'id'         => 0,
        'first_name' => 'HardCodedFirstName',
        'last_name'  => 'HardCodedFirstName',
        'email'      => 'HardCodedFirstName',
        'username'   => 'HardCodedFirstName',
    ];

    return \alina\utils\Data::toObject($u)->id;
}

define('DT_FORMAT_DB', 'Y-m-d H:i:s');
function getNow()
{
    if (defined('DM_REQUEST_TIME')) {
        return date(DT_FORMAT_DB, DM_REQUEST_TIME);
    } else {
        return date(DT_FORMAT_DB);
    }
}

function alinaErrorLog($m, $force = FALSE)
{
    if (ALINA_MODE === 'PROD' && $force === FALSE) {
        return FALSE;
    }
    $message = [
        $m,
        $_SERVER['SERVER_ADDR'],
        isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'UNKNOWN REQUEST_URI',
    ];
    $res     = implode(' | ', $message);
    //message::set($res);
    error_log($res, 0);

    return;
}
