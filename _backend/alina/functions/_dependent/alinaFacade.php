<?php
##################################################
define('ALINA_DT_FORMAT_DB', 'Y-m-d H:i:s');
define('ALINA_DT_FORMAT_DB_D', 'Y-m-d');
define('ALINA_DT_FORMAT_CSV', 'm/d/Y');
define('ALINA_DT_FORMAT_GS', 'M d, Y h:i a O');
define('ALINA_DT_FORMAT_FR', 'ymd');
define('ALINA_DT_FORMAT_CB', 'Ymd');
define('ALINA_DT_FORMAT_ACH', 'ymd');
define('ALINA_DT_FORMAT_ACHT', 'Hi');
define('ALINA_DT_FORMAT_SSC', 'm/d/y');
define('ALINA_DT_FORMAT_LOA', 'F d');
define('ALINA_DT_FORMAT_LOA_LONG', 'F d, Y');
define('ALINA_DT_FORMAT_ISO8601', 'Y-m-d\TH:i:s\Z');
##################################################
define('ALINA_FILE_UPLOAD_KEY', 'userfile');
##################################################

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
    return /*1; */\alina\mvc\model\CurrentUser::obj()->id;
}

function AlinaGetNowInDbFormat()
{
    if (defined('ALINA_TIME')) {
        return date(ALINA_DT_FORMAT_DB, ALINA_TIME);
    } else {
        return date(ALINA_DT_FORMAT_DB);
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
