<?php

namespace alina\Utils;

use function Alina;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\Model\CurrentUser;
use alina\Utils\Data as DataAlias;

use const ALINA_MICROTIME;
use const ALINA_PATH_TO_APP;
use const ALINA_PATH_TO_FRAMEWORK;
use const ALINA_WEB_PATH;

use function AlinaAccessIfAdmin;
use function AlinaCfg;
use function AlinaCfgDefault;
use function GuzzleHttp\json_encode;

use const PHP_EOL;

use ReflectionClass;
use ReflectionMethod;

use function str_starts_with;

use Throwable;

class Sys
{
    ##################################################

    private static array $flagStarted  = [];
    private static array $counterCalls = [];
    public static int $countSome1      = 0;
    public static int $countSome2      = 0;
    public static int $countSome3      = 0;

    protected static function initLogFilePath(?string $fPath = null, $transform = null)
    {
        $fPath = $fPath ?? ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'DEBUG.html';

        switch ($transform) {
            case 'php':
                $fPath = $fPath . '.php';

                break;

            case 'json':
                $fPath = $fPath . '.yaml';

                break;
            default:
                break;
        }

        return $fPath;
    }

    ##################################################

    private static string $fPath;

    protected static function fPath(?string $fPath = null)
    {
        if ($fPath) {
            static::$fPath = $fPath;
        }

        if (empty(static::$fPath)) {
            static::$fPath = ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'DEBUG.html';
        }

        return static::$fPath;
    }

    public static function fDebug($data, $isLog = false, $fPath = null, ?string $transform = null): bool
    {
        try {
            $fPath  = static::initLogFilePath($fPath, $transform);
            $prefix = [];

            ###############################
            # region FLAGS, COUNTERs, etc.
            static::$flagStarted[$fPath]  = static::$flagStarted[$fPath] ?? false;
            static::$counterCalls[$fPath] = isset(static::$counterCalls[$fPath]) ? ++static::$counterCalls[$fPath] : 1;
            # endregion FLAGS, COUNTERs, etc.
            ###############################
            # region CLARIFY TEMPLATE

            switch ($transform) {
                case 'php':
                    $output = $data;

                    ##################################################
                    #region TEMPLATE

                    ob_start();
                    ob_implicit_flush(false);
                    echo PHP_EOL;
                    echo '<?php';
                    echo PHP_EOL;
                    echo sprintf('$XXX_%s = ', static::$counterCalls[$fPath]);
                    echo PHP_EOL;

                    static::dump($output);

                    echo PHP_EOL;
                    echo ';';
                    echo PHP_EOL;
                    echo '?>';
                    echo PHP_EOL;

                    #endregion TEMPLATE
                    ##################################################

                    $output = ob_get_clean();

                    break;

                case 'json':
                    $output = Data::hlpGetBeautifulJsonString($data);

                    break;
                case 'flat':
                    //ToDO:
                    //$output = static::dataToFlat($data);
                    break;
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
                    static::dump($output);
                    echo PHP_EOL;
                    echo '</pre>';
                    echo PHP_EOL;
                    echo '<h2> <<<<<<<<<<<<<<<<<<<< </h2>';
                    echo PHP_EOL;
                    #endregion TEMPLATE
                    ##################################################

                    $output = ob_get_clean();

                    break;
            }

            # endregion CLARIFY TEMPLATE
            ###############################
            # region PREFIX

            if (static::$flagStarted[$fPath] === false) {
                static::$flagStarted[$fPath] = true;

                // WIPE LOG FILE
                if ($isLog === false) {
                    file_put_contents($fPath, PHP_EOL, 0);
                }

                if ($isLog) {
                    $method = static::getReqMethod();
                    $ip     = static::getUserIp();
                    $from   = $_SERVER['HTTP_REFERER']    ?? $ip;
                    $agent  = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI_HTTP_USER_AGENT';

                    $SERVER_NAME = $_SERVER['SERVER_NAME'] ?? getcwd();
                    $REQUEST_URI = $_SERVER['REQUEST_URI'] ?? 'CLI_REQUEST_URI';

                    $prefix = [
                        'LOG STARTED ##########################################################################################',
                        $method,
                        "FROM: $from",
                        "AGENT: $agent",
                        ' ',
                        'SERVER_NAME:' . $SERVER_NAME,
                        'REQUEST_URI:' . $REQUEST_URI,
                        ' ',
                        '$_GET:',
                        json_encode($_GET),
                        ' ',
                        '$_POST:',
                        json_encode($_POST),
                    ];
                }
            }

            if ($isLog) {
                $date   = static::getNow();
                $memory = static::getMemoryUsed() . ' bytes';

                $prefix = array_merge($prefix, [
                    "MEM: $memory",
                    "AT $date",
                ]);

                $prefix = implode(PHP_EOL . '#> ', $prefix);

                file_put_contents($fPath, PHP_EOL, FILE_APPEND);
                file_put_contents($fPath, PHP_EOL, FILE_APPEND);
                file_put_contents($fPath, $prefix, FILE_APPEND);
            }
            # endregion PREFIX
            ###############################
            # region LOG

            file_put_contents($fPath, $output, FILE_APPEND);
            file_put_contents($fPath, PHP_EOL . PHP_EOL, FILE_APPEND);

            # endregion LOG
            ###############################
            # region BactTrace
            $trace = static::getCallStack();
            $trace = var_export($trace, 1);

            file_put_contents($fPath, PHP_EOL . PHP_EOL, FILE_APPEND);
            file_put_contents($fPath, $trace, FILE_APPEND);
            file_put_contents($fPath, PHP_EOL . PHP_EOL, FILE_APPEND);

            # endregion BactTrace
            ###############################

            return true;
        }
        catch (Throwable $e) {
            return false;
        }
    }

    public static function buffer($callback, ...$params)
    {
        ob_start();
        ob_implicit_flush(false);
        call_user_func($callback, $params);
        $output = ob_get_clean();

        return $output;
    }

    public static function returnPrintR($data)
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

    public static function resolvePostDataAsObject()
    {
        $post = $_POST;

        if (empty($post)) {
            $post = file_get_contents('php://input');
        }

        $res = DataAlias::toObject($post);
        Data::itrCastToHealth($res);

        return $res;
    }

    public static function resolveGetDataAsObject()
    {
        $get = $_GET;
        $res = DataAlias::toObject($get);

        return $res;
    }

    public static function isAjax()
    {
        if (isset($_GET['isAjax']) && ! empty($_GET['isAjax']) && $_GET['isAjax'] == 1) {
            return true;
        }

        if (isset($_POST['isAjax']) && ! empty($_POST['isAjax']) && $_POST['isAjax'] == 1) {
            return true;
        }

        // Cross Domain AJAX request.
        if (isset($_SERVER['HTTP_HOST']) && ! empty($_SERVER['HTTP_HOST'])) {
            $h = Url::cleanDomain($_SERVER['HTTP_HOST']);

            if (isset($_SERVER['HTTP_ORIGIN']) && ! empty($_SERVER['HTTP_ORIGIN'])) {
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

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ! empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            // if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'xmlhttprequest') {
            //     return TRUE;
            // }
            if (
                isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'AlinaFetchApi'
            ) {
                return true;
            }
        }

        if (isset($_SERVER['HTTP_REQUESTED_WITH']) && ! empty($_SERVER['HTTP_REQUESTED_WITH'])) {
            return true;
        }

        return false;
    }

    ##################################################

    public static function setCrossDomainHeaders()
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
            'Accept'           => '',
            'X-Requested-With' => '',
            'Content-Type'     => '',
            'Vary'             => '',
            #####
            'fgp'                       => '',
            'Alina-Server-Header'       => '',
            CurrentUser::KEY_USER_ID    => '',
            CurrentUser::KEY_USER_TOKEN => '',
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
        if (isset($_SERVER['HTTP_ORIGIN']) && ! empty($_SERVER['HTTP_ORIGIN'])) {
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
                        exit();
                    }

                    ##################################################
                    break;
            }
        }

        $state_ALREADY_SET = true;

        return true;
    }

    public static function redirect($page, $code = 307, $isToOrigin = false)
    {
        if (
            Str::startsWith($page, 'http://') || Str::startsWith($page, 'https://')
        ) {
            header("Location: $page", true, $code);
            exit();
        }

        ##########
        $get = (object) [];

        if (
            $isToOrigin && isset($_SERVER['HTTP_REFERER']) && ! empty($_SERVER['HTTP_REFERER'])
        ) {
            $url  = Url::cleanDomainWithProtocolAndPort($_SERVER['HTTP_REFERER']);
            $page = implode('/', [
                trim($url, '/'),
                ltrim($page, '/'),
            ]);
        }
        else {
            $page = Html::ref($page);
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
        if (! empty($get)) {
            $page = Url::addGetFromObject($page, $get);
        }

        #####
        header("Location: $page", true, $code);
        exit();
    }

    ##################################################

    public static function getMicroTimeDifferenceFromNow($microtime)
    {
        return microtime(true) - $microtime;
    }

    public static function reportSpentTime($prepend = [], $append = [])
    {
        $main = [
            number_format(static::getMicroTimeDifferenceFromNow(ALINA_MICROTIME), 10, '.', ' '),
            "SPENT",
            $_SERVER['SERVER_ADDR'],
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI_REQUEST_URI',
        ];
        $res = array_merge($prepend, $main, $append);

        return implode(' | ', $res);
    }

    public static function reportMemoryUsed($prepend = [], $append = [])
    {
        $main = [
            number_format(memory_get_usage(), 10, '.', ' '),
            "USED",
            $_SERVER['SERVER_ADDR'],
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI_REQUEST_URI',
        ];
        $res = array_merge($prepend, $main, $append);

        return implode(' | ', $res);
    }

    ##################################################

    /**
     * !!! Requires rework!!!
     * Retrieve Cookies, which are set before page update.
     * @link http://stackoverflow.com/a/34465594/3142281
     */
    public static function getcookie($name = null)
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

    public static function template($fileFullPath, $data = null)
    {
        GlobalRequestStorage::setPlus1('TemplateQueries');
        $fileFullPath = realpath($fileFullPath);
        ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
        ob_implicit_flush(false);
        require $fileFullPath;
        $output = ob_get_clean();

        return $output;
    }

    ##################################################
    ##################################################
    ##################################################

    public static function getReqMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'CLI_REQUEST_METHOD');
    }

    public static function getUserBrowser()
    {
        $browser = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'CLI_HTTP_USER_AGENT';

        return $browser;
    }

    public static function getUserIp()
    {
        // Проверяем X-Forwarded-For (может содержать цепочку IP)
        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            // Берём первый IP — это и есть клиент
            $ip = $ips[0];
        }
        // Или X-Real-IP
        elseif (! empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        // Или REMOTE_ADDR (по умолчанию, если нет прокси)
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        // Валидация IP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }

        // Если IP внутренний (например, 172.19.0.4), возвращаем хотя бы его
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }

    public static function getUserLanguage()
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
    public static function SUPER_DEBUG_INFO()
    {
        return [
            'REQUEST' => Request::obj()->TOTAL_DEBUG_DATA(),
            'ROUTER'  => Alina()->router,
            'META'    => GlobalRequestStorage::getAll(),
        ];
    }

    ##################################################

    public static function dump($data, $depth = 5)
    {
        try {
            var_export($data, 0);
        }
        catch (Throwable $e) {
            static::print_limited_r($data, $depth);
        }
    }

    public static function getNow()
    {
        return date('Y-m-d H:i:s');
    }

    public static function getMemoryUsed()
    {
        return number_format(memory_get_usage(), 0, '.', ' ');
    }

    public static function getCallStack(?array $backtrace = null): array
    {
        if ($backtrace === null) {
            $backtrace = debug_backtrace();
        }

        $stack = [];

        foreach ($backtrace as $trace) {
            $functionName = '';
            $functionName .= $trace['class']    ?? '';
            $functionName .= $trace['type']     ?? '';
            $functionName .= $trace['function'] ?? '';

            $stack[] = $functionName;
        }

        // $stack['$_GET'] = $_GET;
        // $stack['$_POST'] = $_POST;
        return $stack;
    }

    public static function print_limited_r($object, $depth = 5)
    {
        if ($depth == 0) {
            return ''; // Stop the recursion
        }

        $output = print_r($object, true);

        if (is_array($object) || is_object($object)) {
            foreach ($object as $key => $value) {
                $output .= static::print_limited_r($value, $depth - 1) . PHP_EOL . '<===>' . PHP_EOL;
            }
        }

        return $output;
    }

    ##################################################

    public static function validateCurrentRoute(): bool
    {
        $res = false;
        $url = Request::obj()->URL_PATH;
        $wl  = static::getWhiteListRoutes();

        if ($url === '/' || empty($url)) {
            return true;
        }

        foreach ($wl as $route) {
            if (Str::ifContains($url, $route)) {
                return true;
            }
        }

        return $res;
    }

    public static function getWhiteListRoutes(): array
    {
        $res = [];

        $folders = [
            ALINA_PATH_TO_FRAMEWORK . '/mvc/Controller' => '\\' . AlinaCfgDefault('appNamespace') . '\\mvc\\Controller\\',
            ALINA_PATH_TO_APP . '/mvc/Controller'       => '\\' . AlinaCfg('appNamespace') . '\\mvc\\Controller\\',
        ];

        foreach ($folders as $controller => $mamespace) {
            $res = \array_merge($res, Sys::getRouteByControllerAndNamespace($controller, $mamespace));
        }

        return $res;
    }

    public static function getRouteByControllerAndNamespace(string $controllersDir, string $namespacePrefix = ''): array
    {
        $routes = [];

        // Получаем все PHP-файлы в папке
        $files = glob($controllersDir . '/*.php');

        foreach ($files as $file) {
            $fileName  = basename($file, '.php');
            $className = $namespacePrefix . $fileName;

            // Подключаем файл, если класс ещё не объявлен
            if (! class_exists($className)) {
                require_once $file;
            }

            // Проверяем, существует ли класс
            if (! class_exists($className)) {
                continue;
            }

            // Создаём рефлексию класса
            $reflection = new ReflectionClass($className);

            // Получаем только публичные методы
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $methodName = $method->getName();

                // Пропускаем магические методы
                if (str_starts_with($methodName, '__')) {
                    continue;
                }

                // Фильтруем методы, начинающиеся с "action"
                if (str_starts_with($methodName, 'action')) {
                    // Преобразуем имя класса в нижний регистр (без префикса namespace)
                    $controllerName = strtolower($fileName);

                    // Преобразуем имя метода: actionUpsert → upsert
                    $actionName = lcfirst(substr($methodName, 6)); // отрезаем 'action' и делаем первую букву строчной
                    //                    $actionName = preg_replace('/(?<!^)[A-Z]/', '-$0', $actionName); // CamelCase → kebab-case
                    $actionName = strtolower($actionName);

                    // Формируем маршрут
                    $route = $controllerName . '/' . $actionName;

                    $routes[] = $route;

                    if ($actionName === 'index') {
                        $route    = $controllerName . '/';
                        $routes[] = $route;

                        $route    = $controllerName;
                        $routes[] = $route;
                    }
                }
            }
        }

        return $routes;
    }

    ##################################################
    ##################################################
    ##################################################
    ##################################################
}
