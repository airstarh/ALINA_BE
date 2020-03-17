<?php
##################################################
use alina\app;
use alina\GlobalRequestStorage;
use alina\mvc\model\CurrentUser;

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
/**
 * @return app
 * @throws Exception
 */
function Alina()
{
    return app::get();
}

function AlinaCFG($path)
{
    return app::getConfig($path);
}

function AlinaCurrentUserId()
{
    return \alina\mvc\model\CurrentUser::obj()->id;
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

function AlinaResponseSuccess($success = 1)
{
    GlobalRequestStorage::set('alina_response_success', $success);
}

##################################################
#region Access
function AlinaAccessIfLoggedIn()
{
    return CurrentUser::obj()->isLoggedIn();
}

function AlinaAccessIfAdminOrNotProd()
{
    return CurrentUser::obj()->isAdmin() || ALINA_MODE !== 'PROD';
}

function AlinaAccessIfAdmin()
{
    return CurrentUser::obj()->isAdmin();
}

function AlinaAccessIfModerator()
{
    return CurrentUser::obj()->hasRole('moderator');
}

function AlinaAccessIfOwner($id)
{
    return CurrentUser::obj()->id === $id;
}

function AlinaAccessIfAdminOrModeratorOrOwner($id)
{
    return
        AlinaAccessIfOwner($id)
        ||
        AlinaAccessIfAdmin()
        ||
        AlinaAccessIfModerator();
}

#endregion Access
##################################################
/**
 * https://stackoverflow.com/questions/3964793/php-case-insensitive-version-of-file-exists
 */
function Alina_file_exists($fileName, $caseSensitive = FALSE)
{
    if (file_exists($fileName)) {
        return $fileName;
    }
    if ($caseSensitive) return FALSE;
    // Handle case insensitive requests
    $directoryName     = dirname($fileName);
    $fileArray         = glob($directoryName . '/*', GLOB_NOSORT);
    $fileNameLowerCase = strtolower($fileName);
    foreach ($fileArray as $file) {
        if (strtolower($file) == $fileNameLowerCase) {
            return $file;
        }
    }

    return FALSE;
}
##################################################
