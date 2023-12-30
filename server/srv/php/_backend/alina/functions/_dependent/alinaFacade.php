<?php
##################################################
use alina\App;
use alina\GlobalRequestStorage;
use alina\Message;
use alina\mvc\Model\CurrentUser;
use alina\Router;
use alina\Utils\Request;
use alina\Utils\Sys;

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
 * @return App
 * @throws Exception
 */
function Alina()
{
    return App::get();
}

function AlinaCfg($path)
{
    return App::getConfig($path);
}

function AlinaCfgDefault($path)
{
    return App::getConfigDefault($path);
}

function AlinaGetNowInDbFormat()
{
    if (defined('ALINA_TIME')) {
        return date(ALINA_DT_FORMAT_DB, ALINA_TIME);
    } else {
        return date(ALINA_DT_FORMAT_DB);
    }
}

function AlinaResponseSuccess($success = 1)
{
    static $flagAlreadySet = 0;
    if ($success != 1 && $flagAlreadySet === 0) {
        Message::setDanger(___('Response is not success'));
        $flagAlreadySet = 1;
    }
    GlobalRequestStorage::set('alina_response_success', $success);
}

function AlinaIsResponseSuccess()
{
    return GlobalRequestStorage::get('alina_response_success');
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

function AlinaAccessIfOwner($owner_id)
{
    return CurrentUser::obj()->id() == $owner_id;
}

function AlinaAccessIfAdminOrModeratorOrOwner($owner_id)
{
    return
        AlinaAccessIfOwner($owner_id)
        ||
        AlinaAccessIfAdmin()
        ||
        AlinaAccessIfModerator();
}

function AlinaAccessIfAdminOrModerator()
{
    return
        AlinaAccessIfAdmin()
        ||
        AlinaAccessIfModerator();
}

#####
function AlinaReject($page = null, $code = 303, $message = 'ACCESS DENIED')
{
    AlinaResponseSuccess(0);
    Message::setDanger($message);
    if ($page) {
        Sys::redirect($page, $code);
    } else {
        Request::obj()->METHOD = 'GET';
        Alina()->mvcGo('Root', 'AccessDenied', [$code]);
    }
}

function AlinaRejectIfNotLoggedIn($code = 303)
{
    if (!AlinaAccessIfLoggedIn()) {
        AlinaReject('/auth/login', $code);
    }
}

function AlinaRejectIfNotAdmin()
{
    if (!AlinaAccessIfAdmin()) {
        AlinaReject(null, 403, ___('DENIED'));
    }
}

function AlinaRejectIfNotAdminOrModeratorOrOwner($id)
{
    if (!AlinaAccessIfAdminOrModeratorOrOwner($id)) {
        AlinaReject(null, 403, ___('DENIED'));
    }
}

function AlinaRejectIfNotAdminOrModerator()
{
    if (!AlinaAccessIfAdminOrModerator()) {
        AlinaReject(null, 403, ___('DENIED'));
    }
}

function AlinaRedirectIfNotAjax($to = '/#/', $code = 303, $isToOrigin = false)
{
    if (!Request::obj()->AJAX) {
        Sys::redirect($to, $code, $isToOrigin);
    }
}

#endregion Access
##################################################
/**
 * https://stackoverflow.com/questions/3964793/php-case-insensitive-version-of-file-exists
 */
function Alina_file_exists($fileName, $caseSensitive = false)
{
    if (file_exists($fileName)) {
        return $fileName;
    }
    if ($caseSensitive) return false;
    // Handle case insensitive requests
    $directoryName     = dirname($fileName);
    $fileArray         = glob($directoryName . '/*', GLOB_NOSORT);
    $fileNameLowerCase = strtolower($fileName);
    foreach ($fileArray as $file) {
        if (strtolower($file) == $fileNameLowerCase) {
            return $file;
        }
    }

    return false;
}

##################################################
function AlinaGetCurrentDomainUrl()
{
    $protocol   = $_SERVER['REQUEST_SCHEME'];
    $domainName = $_SERVER['HTTP_HOST'];
    $parts      = [
        $protocol,
        '://',
        $domainName,
    ];

    return implode('', $parts);
}

function AlinaDefineTagRelAlternateUrl()
{
    $domain = AlinaGetCurrentDomainUrl();
    $parts  = [
        $domain,
        AlinaCfg('frontend/path'),
        '/#/',
        Router::obj()->pathSys,
    ];

    return implode('', $parts);
}

function AlinaDefineTagRelCanonicalUrl()
{
    $domain = AlinaGetCurrentDomainUrl();
    $parts  = [
        $domain,
        '/',
        Router::obj()->pathSys,
    ];

    return implode('', $parts);
}

function AlinaFePath($routeName)
{
    $frontend = AlinaCfg('frontend');
    $blocks   = [];
    $blocks[] = $frontend['path'];
    $blocks[] = $frontend[$routeName];

    return \alina\Utils\FS::buildPathFromBlocks($blocks);
}

##################################################
function ___($str, $loc = 'ru_RU')
{
    return \alina\Services\AlinaTranslate::obj()->t($str, $loc);
}

##################################################
#region DEBUG
function AlinaDebugJson($data)
{
    \alina\Utils\Sys::fDebug($data, FILE_APPEND, null, 'json');
}

function AlinaDebugTime($prepend = [], $append = [])
{
    $data = \alina\Utils\Sys::reportSpentTime($prepend, $append);
    \alina\Utils\Sys::fDebug($data, FILE_APPEND, null, 'json');
}

function AlinaDebugMemory($prepend = [], $append = [])
{
    $data = \alina\Utils\Sys::reportMemoryUsed($prepend, $append);
    \alina\Utils\Sys::fDebug($data, FILE_APPEND, null, 'json');
}
#endregion DEBUG
##################################################
