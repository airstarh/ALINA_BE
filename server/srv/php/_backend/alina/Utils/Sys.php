<?php

namespace alina\Utils;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\Model\CurrentUser;
use alina\Utils\Data as DataAlias;
use TypeError;

class Sys
{
    ##################################################
    private static string $fPath;

    static protected function fPath(string $fPath = null)
    {
        if ($fPath) {
            static::$fPath = $fPath;
        }

        if (empty(static::$fPath)) {
            static::$fPath = ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'DEBUG.html';
        }

        return static::$fPath;
    }


    static public function fDebug($data, $flags = FILE_APPEND, $fPath = null, string $transform = null): void
    {
        try {

            static $flagStarted = [];

            $fPath = static::fPath($fPath);

            switch ($transform) {
                case 'json':
                    $output = DataAlias::hlpGetBeautifulJsonString($data);
                    $fPath = $fPath . '.yaml';
                    break;
                case 'flat':
                    //ToDO:
                    //$output = static::dataToFlat($data);
                    break;
                case 'html':
                default:
                    $output = $data;
                    ##################################################
                    #region TEMPLATE
                    ob_start();
                    ob_implicit_flush(false);
                    echo PHP_EOL;
                    echo '<h1> >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> </h1>';
                    echo PHP_EOL;
                    echo '<pre>';
                    echo PHP_EOL;
                    echo var_export($output, 1);
                    echo '</pre>';
                    echo PHP_EOL;
                    echo '<h2> <<<<<<<<<<<<<<<<<<<< </h2>';
                    echo PHP_EOL;
                    #endregion TEMPLATE
                    ##################################################
                    $output = ob_get_clean();
                    break;
            }

            if (empty($flagStarted[$fPath])) {
                file_put_contents($fPath, PHP_EOL, 0);
                $flagStarted[$fPath] = true;
            }
            file_put_contents($fPath, $output, $flags);
            file_put_contents($fPath, PHP_EOL . PHP_EOL, FILE_APPEND);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getLine());
            error_log($e->getTraceAsString());
        }
    }

    static public function buffer($callback, ...$params)
    {
        ob_start();
        ob_implicit_flush(false);
        call_user_func($callback, $params);
        $output = ob_get_clean();

        return $output;
    }

    static public function returnPrintR($data)
    {
        ob_start();
        ob_implicit_flush(false);
        echo '<hr><pre>';
        echo PHP_EOL;
        print_r($data);
        echo PHP_EOL;
        echo '</pre>';
        $output = ob_get_clean();

        return $output;
    }

    ##################################################
    static public function resolvePostDataAsObject()
    {
        $post = $_POST;
        if (empty($post)) {
            $post = file_get_contents('php://input');
        }
        $res = DataAlias::toObject($post);
        Data::itrCastToHealth($res);
        return $res;
    }

    static public function resolveGetDataAsObject()
    {
        $get = $_GET;
        $res = DataAlias::toObject($get);

        return $res;
    }

    static public function isAjax()
    {
        if (isset($_GET['isAjax']) && !empty($_GET['isAjax']) && $_GET['isAjax'] == 1) {
            return true;
        }
        if (isset($_POST['isAjax']) && !empty($_POST['isAjax']) && $_POST['isAjax'] == 1) {
            return true;
        }
        // Cross Domain AJAX request.
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            $h = Url::cleanDomain($_SERVER['HTTP_HOST']);
            if (isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])) {
                $o = Url::cleanDomain($_SERVER['HTTP_ORIGIN']);
                if ($o !== $h) {
                    return true;
                }
            }
            // if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            //     $r = Url::cleanDomain($_SERVER['HTTP_REFERER']);
            //     if ($r !== $h) {
            //         return TRUE;
            //     }
            // }
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            // if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'xmlhttprequest') {
            //     return TRUE;
            // }
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'AlinaFetchApi') {
                return true;
            }
        }
        if (isset($_SERVER['HTTP_REQUESTED_WITH']) && !empty($_SERVER['HTTP_REQUESTED_WITH'])) {
            return true;
        }

        return false;
    }

    ##################################################
    static public function setCrossDomainHeaders()
    {
        static $state_ALREADY_SET = false;
        if ($state_ALREADY_SET) {
            return true;
        }
        //@link https://stackoverflow.com/questions/298745/how-do-i-send-a-cross-domain-post-request-via-javascript
        //ToDo: PROD! Security!
        #####
        $allowedHeaders = [
            'Accept-Encoding'                => '',
            'Accept-Language'                => '',
            'Access-Control-Request-Headers' => '',
            'Access-Control-Request-Method'  => '',
            'Connection'                     => '',
            'Host'                           => '',
            'Origin'                         => '',
            'Referer'                        => '',
            'User-Agent'                     => '',
            'Cache-Control'                  => '',
            'Access-Control-Allow-Origin'    => '',
            #####
            'Accept'                         => '',
            'X-Requested-With'               => '',
            'Content-Type'                   => '',
            'Vary'                           => '',
            #####
            'fgp'                            => '',
            'Alina-Server-Header'            => '',
            CurrentUser::KEY_USER_ID         => '',
            CurrentUser::KEY_USER_TOKEN      => '',
        ];
        $allowedHeaders = array_keys($allowedHeaders);
        $allowedHeaders = implode(', ', $allowedHeaders);
        header("Access-Control-Allow-Headers: {$allowedHeaders}");
        header("Access-Control-Expose-Headers: {$allowedHeaders}");
        #####
        #region Custom headers for tests
        header('Alina-Server-Header: Hello, from Alina');
        #endregion Custom headers for tests
        #####
        #region Fix for Chrome Back button
        //header('Vary: X-Requested-With');
        header('Vary:Content-Type');
        //header('Vary: Accept, X-Requested-With');
        //header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Cache-Control: private, max-age=0, s-max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        //header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        #region Fix for Chrome Back button
        #####
        if (isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])) {
            //if (TRUE) {
            switch ($_SERVER['HTTP_ORIGIN']) {
                default:
                    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                    header("Access-Control-Allow-Credentials: true");
                    header('Access-Control-Max-Age: 666');
                    #####
                    ##################################################
                    $method = strtoupper($_SERVER['REQUEST_METHOD']);
                    if ($method === 'OPTIONS') {
                        echo 'ok';
                        exit;
                    }
                    ##################################################
                    break;
            }
        }
        $state_ALREADY_SET = true;

        return true;
    }

    static public function redirect($page, $code = 307, $isToOrigin = false)
    {
        if (\alina\Utils\Str::startsWith($page, 'http://')
            || \alina\Utils\Str::startsWith($page, 'https://')
        ) {
            header("Location: $page", true, $code);
            die();
        }
        ##########
        $get = (object)[];
        if ($isToOrigin
            &&
            isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])
        ) {
            $url  = Url::cleanDomainWithProtocolAndPort($_SERVER['HTTP_REFERER']);
            $page = implode('/', [
                trim($url, '/'),
                ltrim($page, '/'),
            ]);
        }
        else {
            $page = \alina\Utils\Html::ref($page);
        }
        #####
        $messages = Message::returnAllMessages();
        if (count($messages) > 0) {
            $get->{Message::$MESSAGE_GET_KEY} = json_encode($messages, JSON_UNESCAPED_UNICODE);
        }
        if (AlinaAccessIfAdmin()) {
            $messages_admin = MessageAdmin::returnAllMessages();
            if (count($messages_admin) > 0) {
                $get->{MessageAdmin::$MESSAGE_GET_KEY} = json_encode($messages_admin, JSON_UNESCAPED_UNICODE);
            }
        }
        #####
        if (!empty($get)) {
            $page = \alina\Utils\Url::addGetFromObject($page, $get);
        }
        #####
        header("Location: $page", true, $code);
    }

    ##################################################
    static public function getMicroTimeDifferenceFromNow($microtime)
    {
        return microtime(true) - $microtime;
    }

    static public function reportSpentTime($prepend = [], $append = [])
    {
        $main = [
            number_format(static::getMicroTimeDifferenceFromNow(ALINA_MICROTIME), 10, '.', ' '),
            "SPENT",
            $_SERVER['SERVER_ADDR'],
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'UNKNOWN REQUEST_URI',
        ];
        $res  = array_merge($prepend, $main, $append);

        return implode(' | ', $res);
    }

    static public function reportMemoryUsed($prepend = [], $append = [])
    {
        $main = [
            number_format(memory_get_usage(), 10, '.', ' '),
            "USED",
            $_SERVER['SERVER_ADDR'],
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'UNKNOWN REQUEST_URI',
        ];
        $res  = array_merge($prepend, $main, $append);

        return implode(' | ', $res);
    }

    ##################################################

    /**
     * !!! Requires rework!!!
     * Retrieve Cookies, which are set before page update.
     * @link http://stackoverflow.com/a/34465594/3142281
     */
    static public function getcookie($name = null)
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

    ##################################################
    static public function template($fileFullPath, $data = null)
    {
        $fileFullPath = realpath($fileFullPath);
        ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
        ob_implicit_flush(false);
        require($fileFullPath);
        $output = ob_get_clean();

        return $output;
    }

    ##################################################
    ##################################################
    ##################################################
    static public function getReqMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    static public function getUserBrowser()
    {
        $browser = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';

        return $browser;
    }

    static public function getUserIp()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    static public function getUserLanguage()
    {
        $lang = 'en';
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }

        return $lang;
    }

    ##################################################

    /**
     * @return array
     */
    static public function SUPER_DEBUG_INFO()
    {
        return [
            'REQUEST' => Request::obj()->TOTAL_DEBUG_DATA(),
            'ROUTER'  => Alina()->router,
            'META'    => GlobalRequestStorage::getAll(),
        ];
    }

    ##################################################
    ##################################################
    ##################################################
    ##################################################
    ##################################################
    ##################################################
}
