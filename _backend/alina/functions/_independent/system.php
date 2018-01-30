<?php

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
    $postOriginal = toObject($_POST);
    $postStdIn    = toObject(file_get_contents('php://input'));
    $res = mergeSimpleObjects($postOriginal, $postStdIn);
    return $res;
}
#endregion Drafts