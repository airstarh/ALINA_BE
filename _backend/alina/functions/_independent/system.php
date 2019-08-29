<?php

//use function alina\functions\_independent\contains;

use alina\message;

function requireAllFromDir($dir)
{

}

function fDebug($data, $flags = FILE_APPEND, $fPath = NULL)
{
    if (!isset($fPath) || empty($fPath)) {
        $fPath = ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'deleteOnProdDebug.html';
    }
    ob_start();
    ob_implicit_flush(FALSE);
    echo '<hr><pre>';
    echo PHP_EOL;
    print_r($data);
    echo PHP_EOL;
    echo '</pre>';
    $output = ob_get_clean();
    file_put_contents($fPath, $output, $flags);
    file_put_contents($fPath, PHP_EOL . PHP_EOL, FILE_APPEND);
}

function alinaSafeEcho($data)
{
    ob_start();
    ob_implicit_flush(FALSE);
    echo '<hr><pre>';
    echo PHP_EOL;
    print_r($data);
    echo PHP_EOL;
    echo '</pre>';
    $output = ob_get_clean();

    return $output;
}

function getMicroTimeDifferenceFromNow($microtime)
{
    return microtime(TRUE) - $microtime;
}

function reportSpentTime($prepend = [], $append = [])
{
    $main = [
        "SPENT",
        $_SERVER['SERVER_ADDR'],
        getMicroTimeDifferenceFromNow(ALINA_MICROTIME),
        isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'UNKNOWN REQUEST_URI',
    ];
    $res  = array_merge($prepend, $main, $append);

    return implode(' | ', $res);
}

//ToDo: Move to dependent functions
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

#region Drafts
/**
 * !!! Requires rework!!!
 * Retrieve Cookies, which are set before page update.
 * @link http://stackoverflow.com/a/34465594/3142281
 */
function getcookie($name = NULL)
{
    $cookies = [];
    $headers = headers_list();
    // see http://tools.ietf.org/html/rfc6265#section-4.1.1
    foreach ($headers as $header) {
        if (strpos($header, 'Set-Cookie: ') === 0) {
            $value = str_replace('&', urlencode('&'), substr($header, 12));
            parse_str(current(explode(';', $value, 1)), $pair);
            $cookies = array_merge_recursive($cookies, $pair);
        }
    }
    if (isset($name)) {
        return $cookies[$name];
    }

    return $cookies;
}

function resolvePostDataAsObject()
{
    $post = $_POST;

    #region Define Content TYpe
    if (isset($_SERVER['CONTENT_TYPE']) && !empty($_SERVER['CONTENT_TYPE'])) {
        $contentType = strtolower($_SERVER['CONTENT_TYPE']);
        if (hlpStrContains($contentType, 'json')) {
            $post = file_get_contents('php://input');
        }
    }
    #endregion Define Content TYpe
    $res = toObject($post);

    return $res;
}

function isAjax()
{
    // Cross Domain AJAX request.
//    if (isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])) {
//        return TRUE;
//    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return TRUE;
    }
    if (strtolower(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest') {
        return TRUE;
    }
    if (isset($_GET['isAjax']) && !empty($_GET['isAjax'])) {
        return TRUE;
    }
    if (isset($_POST['isAjax']) && !empty($_POST['isAjax'])) {
        return TRUE;
    }

    return FALSE;
}

function setCrossDomainHeaders()
{
    //@link https://stackoverflow.com/questions/298745/how-do-i-send-a-cross-domain-post-request-via-javascript
    //ToDo: PROD! Security!
    if (isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])) {
        switch ($_SERVER['HTTP_ORIGIN']) {
            default:
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                //header("Access-Control-Allow-Origin: *");
                //header("Vary: Origin");
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 666');
                header('Alina-Server-Header: Hello, user');
                $allowedHeaders = [
                    //'Authorization'                  => '',
                    //'Access-Control-Allow-Headers'   => '',
                    'Origin'                         => '',
                    'Accept'                         => '',
                    'X-Requested-With'               => '',
                    'Content-Type'                   => '',
                    'Access-Control-Request-Method'  => '',
                    'Access-Control-Request-Headers' => '',
                    //'userHeader'                     => '',
                    //'passwordHeader'                 => '',
                    'Authorization'                  => '',
                ];
                $allowedHeaders = array_keys($allowedHeaders);
                $allowedHeaders = implode(', ', $allowedHeaders);
                header("Access-Control-Allow-Headers: {$allowedHeaders}");
                header("Access-Control-Expose-Headers: {$allowedHeaders}");
                //header('Access-Control-Allow-Credentials: false');
                break;
        }
    }
}

#endregion Drafts
