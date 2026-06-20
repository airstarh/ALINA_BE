<?php

##################################################

use alina\App;
use alina\MessageAdmin;
use alina\Utils\Data;
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

function Alina(): App
{
    return App::get();
}

function AlinaCfg($path)
{
    return Alina()::getConfig($path);
}

function AlinaCfgDefault($path)
{
    return Alina()::getConfigDefault($path);
}

function AlinaGetNowInDbFormat()
{
    if (defined('ALINA_TIME')) {
        return date(ALINA_DT_FORMAT_DB, ALINA_TIME);
    }
    else {
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
            AlinaAccessIfOwner($owner_id) || AlinaAccessIfAdmin() || AlinaAccessIfModerator();
}

function AlinaAccessIfAdminOrModerator()
{
    return
            AlinaAccessIfAdmin() || AlinaAccessIfModerator();
}

#####

function AlinaReject($page = null, $code = 403, $message = 'ACCESS DENIED', $messageParams = [])
{
    AlinaResponseSuccess(0);
    Message::setDanger($message, $messageParams);

    if ($page) {
        Sys::redirect($page, $code);
    }
    else {
        Request::obj()::resetToGet();
        Alina()->mvcGo('Root', 'AccessDenied', [$code]);
    }
}

/**
 * What is correct HTTP status code when redirecting to a login page?
 * https://stackoverflow.com/questions/2839585/what-is-correct-http-status-code-when-redirecting-to-a-login-page
 */
function AlinaRejectIfNotLoggedIn($code = 302)
{
    if (! AlinaAccessIfLoggedIn()) {
        AlinaReject(AlinaCfg('frontend/login'), $code);
    }
}

function AlinaRejectIfNotAdmin()
{
    if (! AlinaAccessIfAdmin()) {
        AlinaReject(null, 403, ___('DENIED'));
    }
}

function AlinaRejectIfNotAdminOrModeratorOrOwner($id)
{
    if (! AlinaAccessIfAdminOrModeratorOrOwner($id)) {
        AlinaReject(null, 403, ___('DENIED'));
    }
}

function AlinaRejectIfNotAdminOrModerator()
{
    if (! AlinaAccessIfAdminOrModerator()) {
        AlinaReject(null, 403, ___('DENIED'));
    }
}

function AlinaRedirectIfNotAjax($to = '/#/', $code = 303, $isToOrigin = false)
{
    if (! Request::obj()->AJAX) {
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

    if ($caseSensitive) {
        return false;
    }
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
    $protocol   = $_SERVER['REQUEST_SCHEME'] ?? 'CLI_REQUEST_SCHEME';
    $domainName = $_SERVER['HTTP_HOST']      ?? 'CLI_HTTP_HOST';
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

    return alina\Utils\FS::buildPathFromBlocks($blocks);
}

##################################################

function ___($str, $loc = 'ru_RU')
{
    try {
        return alina\Services\AlinaTranslate::obj()->t($str, $loc);
    }
    catch (Exception $e) {
        return $str;
    }
}

##################################################

function AlinaEchoDraft($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    echo '<br>';

    AlinaExit($data);
}

function AlinaEcho(string $string)
{
    echo $string;

    AlinaExit($string);
}

function AlinaExit($data)
{
    try {
        $msg            = null;
        $flagSuspicious = AlinaIsResponseSuccess() === 1 ? 0 : 1;

        if ($flagSuspicious) {
            $msg        = [];
            $msg['usr'] = Data::hlpGetBeautifulJsonString(Message::returnAllMessages());
            $msg['adm'] = Data::hlpGetBeautifulJsonString(MessageAdmin::returnAllMessages());
        }

        $msgString = $msg ? alina\Utils\Str::anyToString($msg) : null;

        alina\Watcher::obj()->answer([
            'answer'     => $msgString,
            'suspicious' => $flagSuspicious,
            'controller' => GlobalRequestStorage::obj()->get('BaseModelQueries'),
            'action'     => GlobalRequestStorage::obj()->get('TemplateQueries'),
        ]);
    }
    catch (Throwable $e) {
        error_log('salam');
    }
    exit();
}

##################################################
#region DEBUG

function AlinaDebug($data)
{
    return Sys::fDebug($data, true, null, 'php');
}

function AlinaDebugJson($data)
{
    Sys::fDebug('>>>>>>>>>>', FILE_APPEND, null, 'json');
    Sys::fDebug($data, FILE_APPEND, null, 'json');
}

function AlinaDebugTime($prepend = [], $append = [])
{
    $data = Sys::reportSpentTime($prepend, $append);
    Sys::fDebug($data, FILE_APPEND, null, 'json');
}

function AlinaDebugMemory($prepend = [], $append = [])
{
    $data = Sys::reportMemoryUsed($prepend, $append);
    Sys::fDebug($data, FILE_APPEND, null, 'json');
}

#endregion DEBUG
##################################################
