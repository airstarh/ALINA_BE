<?php

namespace alina\utils;

use alina\app;
use alina\mvc\model\CurrentUser;

class Sys
{
    ##################################################
    static public function fDebug($data, $flags = FILE_APPEND, $fPath = NULL)
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

    static public function alinaSafeEcho($data)
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

    ##################################################
    static public function resolvePostDataAsObject()
    {
        $post = $_POST;

        if (empty($post)) {
            $post = file_get_contents('php://input');
        }
        $res = \alina\utils\Data::toObject($post);

        return $res;
    }

    static public function resolveGetDataAsObject()
    {
        $get = $_GET;
        $res = \alina\utils\Data::toObject($get);

        return $res;
    }

    // ToDo: Rewrite
    static public function isAjax()
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

    ##################################################
    static public function setCrossDomainHeaders()
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
                    header('Alina-Server-Header: Hello, from Alina');
                    $allowedHeaders = [
                        'Origin'                         => '',
                        'Accept'                         => '',
                        'X-Requested-With'               => '',
                        'Content-Type'                   => '',
                        'Access-Control-Request-Method'  => '',
                        'Access-Control-Request-Headers' => '',
                        #####
                        'Authorization'                  => '',
                        CurrentUser::KEY_USER_ID          => '',
                        CurrentUser::KEY_USER_TOKEN       => '',
                    ];
                    $allowedHeaders = array_keys($allowedHeaders);
                    $allowedHeaders = implode(', ', $allowedHeaders);
                    header("Access-Control-Allow-Headers: {$allowedHeaders}");
                    header("Access-Control-Expose-Headers: {$allowedHeaders}");
                    header("Access-Control-Allow-Credentials: true");
                    header("Authorization: QQQ");

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
    }

    static public function redirect($page, $code = 301)
    {
        if (\alina\utils\Str::startsWith($page, 'http://')
            || \alina\utils\Str::startsWith($page, 'https://')
        ) {
            header("Location: $page", TRUE, $code);
            die();
        }

        $page = \alina\utils\Html::ref($page);
        header("Location: $page", TRUE, $code);
        die();
    }

    ##################################################
    static public function getMicroTimeDifferenceFromNow($microtime)
    {
        return microtime(TRUE) - $microtime;
    }

    //ToDO: FW dependent!!!
    static public function reportSpentTime($prepend = [], $append = [])
    {
        $main = [
            "SPENT",
            $_SERVER['SERVER_ADDR'],
            static::getMicroTimeDifferenceFromNow(ALINA_MICROTIME),
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
    static public function getcookie($name = NULL)
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
    static public function template($fileFullPath, $data = NULL)
    {
        $fileFullPath = realpath($fileFullPath);
        ob_start(NULL, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
        ob_implicit_flush(FALSE);
        require($fileFullPath);
        $output = ob_get_clean();

        return $output;
    }

    ##################################################
    ##################################################
    ##################################################
    static Public function getReqMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    static Public function getUserBrowser()
    {
        $browser = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN';

        return $browser;
    }

    static Public function getUserIp()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    static Public function getUserLanguage()
    {
        //$l = 'en';
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        return $lang;
    }

    ##################################################

    /**
     * @return array
     */
    static public function SUPER_DEBUG_INFO()
    {
        $res = array_merge(
            Request::obj()->TOTAL_DEBUG_DATA(),
            [
                'ROUTER' => app::get()->router,
            ]
        );

        return $res;
    }

    ##################################################
    ##################################################
    ##################################################
    ##################################################
    ##################################################
    ##################################################
}
