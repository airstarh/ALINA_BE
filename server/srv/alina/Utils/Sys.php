<?php

namespace alina\Utils;

use function Alina;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\Model\CurrentUser;
use alina\Utils\Data as Data;

use function GuzzleHttp\json_encode;

use stdClass;
use Throwable;

class Sys
{
    private static array $flagStarted  = [];
    private static array $counterCalls = [];
    public static int $countSome1      = 0;
    public static int $countSome2      = 0;
    public static int $countSome3      = 0;

    protected static function initLogFilePath(?string $fPath = null, $transform = null)
    {
        $hostName = $_SERVER['HTTP_HOST'] ?? 'CLI';
        $fileName = "DEBUG.$hostName.html";

        if (empty($fPath)) {
            $tmp = ini_get('error_log');

            if ($tmp) {
                $tmp   = dirname($tmp);
                $fPath = $tmp . DIRECTORY_SEPARATOR . $fileName;
            }
            else {
                $fPath = '/var/log/php/' . $fileName;
            }
        }

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

    private static string $fPath;

    protected static function fPath(?string $fPath = null)
    {
        if ($fPath) {
            static::$fPath = $fPath;
        }

        if (empty(static::$fPath)) {
            static::$fPath = \ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'DEBUG.html';
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
                    echo \PHP_EOL;
                    echo '<?php';
                    echo \PHP_EOL;
                    echo sprintf('$XXX_%s = ', static::$counterCalls[$fPath]);
                    echo \PHP_EOL;

                    static::dump($output);

                    echo \PHP_EOL;
                    echo ';';
                    echo \PHP_EOL;
                    echo '?>';
                    echo \PHP_EOL;

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
                    echo \PHP_EOL;
                    echo '<h1> >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> </h1>';
                    echo \PHP_EOL;
                    echo '<pre>';
                    echo \PHP_EOL;
                    static::dump($output);
                    echo \PHP_EOL;
                    echo '</pre>';
                    echo \PHP_EOL;
                    echo '<h2> <<<<<<<<<<<<<<<<<<<< </h2>';
                    echo \PHP_EOL;
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
                    file_put_contents($fPath, \PHP_EOL, 0);
                }

                if ($isLog) {
                    $method = static::getReqMethod();
                    $ip     = static::getUserIp();
                    $from   = $_SERVER['HTTP_REFERER']    ?? $ip;
                    $agent  = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';

                    $SERVER_NAME = $_SERVER['SERVER_NAME'] ?? getcwd();
                    $REQUEST_URI = $_SERVER['REQUEST_URI'] ?? 'CLI';

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

                $prefix = implode(\PHP_EOL . '#> ', $prefix);

                file_put_contents($fPath, \PHP_EOL, FILE_APPEND);
                file_put_contents($fPath, \PHP_EOL, FILE_APPEND);
                file_put_contents($fPath, $prefix, FILE_APPEND);
            }
            # endregion PREFIX
            ###############################
            # region LOG

            file_put_contents($fPath, $output, FILE_APPEND);
            file_put_contents($fPath, \PHP_EOL . \PHP_EOL, FILE_APPEND);

            # endregion LOG
            ###############################
            # region BactTrace
            $trace = static::getCallStack();
            $trace = var_export($trace, 1);

            file_put_contents($fPath, \PHP_EOL . \PHP_EOL, FILE_APPEND);
            file_put_contents($fPath, $trace, FILE_APPEND);
            file_put_contents($fPath, \PHP_EOL . \PHP_EOL, FILE_APPEND);

            # endregion BactTrace
            ###############################

            return true;
        }
        catch (Throwable $e) {
            error_log($e->getMessage());
            error_log("Cannot write file debug: $fPath");

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
        echo \PHP_EOL;
        print_r($data);
        echo \PHP_EOL;
        echo '</pre>';
        $output = ob_get_clean();

        return $output;
    }

    public static function resolvePostDataAsObject()
    {
        $post = $_POST ?? [];

        if (empty($post)) {
            $post = file_get_contents('php://input');
        }

        $res = Data::toObject($post);
        Data::itrCastToHealth($res);

        return $res;
    }

    public static function resolveGetDataAsObject()
    {
        $get = $_GET ?? [];
        $res = Data::toObject($get);

        return $res;
    }

    public static function isAjax(): bool
    {
        if (isset($_GET['isAjax']) && $_GET['isAjax'] === '1') {
            return true;
        }

        if (isset($_POST['isAjax']) && $_POST['isAjax'] === '1') {
            return true;
        }

        $xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;

        if ($xRequestedWith === 'AlinaFetchApi') {
            return true;
        }

        if (strtolower((string)$xRequestedWith) === 'xmlhttprequest') {
            return true;
        }

        if (! empty($_SERVER['HTTP_REQUESTED_WITH'])) {
            return true;
        }

        return false;
    }

    public static function setCrossDomainHeaders()
    {
        static $state_ALREADY_SET = false;

        if ($state_ALREADY_SET) {
            return true;
        }

        // Always set CORS headers for all responses
        if (! empty($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Accept, X-Requested-With, Content-Type, Origin, Authorization, fgp, Alina-Server-Header, user_id, user_token");
            header("Access-Control-Expose-Headers: Accept, X-Requested-With, Content-Type, Origin, Authorization, fgp, Alina-Server-Header, user_id, user_token");
            header('Access-Control-Max-Age: 666');

            // Handle preflight
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                echo 'ok';
                AlinaExit(__FUNCTION__);
            }
        }

        // Other headers
        header('Alina-Server-Header: Hello, from Alina');
        header('Vary: Content-Type');
        header('Cache-Control: private, max-age=0, s-max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $state_ALREADY_SET = true;

        return true;
    }

    public static function redirect($page, $code = 307, $isToOrigin = false)
    {
        if (
            Str::startsWith($page, 'http://') || Str::startsWith($page, 'https://')
        ) {
            header("Location: $page", true, $code);
            AlinaExit('raw redirect');
        }

        ##########
        $get = new stdClass();

        if (
            $isToOrigin && ! empty($_SERVER['HTTP_REFERER'])
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

        if (\AlinaAccessIfAdmin()) {
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
        AlinaExit('complex redirect');
    }

    public static function getMicroTimeDifferenceFromNow($microtime)
    {
        return microtime(true) - $microtime;
    }

    public static function reportSpentTime($prepend = [], $append = [])
    {
        $main = [
            number_format(static::getMicroTimeDifferenceFromNow(\ALINA_MICROTIME), 10, '.', ' '),
            "SPENT",
            $_SERVER['SERVER_ADDR'],
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI',
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
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI',
        ];
        $res = array_merge($prepend, $main, $append);

        return implode(' | ', $res);
    }

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
                $value = str_replace('&', urlencode('&'), substr((string) $header, 12));
                parse_str(current(explode(';', $value, 1)), $pair);
                $cookies = array_merge_recursive($cookies, $pair);
            }
        }

        if (isset($name)) {
            return $cookies[$name];
        }

        return $cookies;
    }

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

    public static function getReqMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'CLI');
    }

    public static function getUserBrowser()
    {
        $browser = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'CLI';

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
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        }

        // Валидация IP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }

        // Если IP внутренний (например, 172.19.0.4), возвращаем хотя бы его
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'CLI';
    }

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
                $output .= static::print_limited_r($value, $depth - 1) . \PHP_EOL . '<===>' . \PHP_EOL;
            }
        }

        return $output;
    }

    public static function getWhiteListController(): array
    {
        $res = [];

        $folders = [
            \ALINA_PATH_TO_FRAMEWORK . '/mvc/Controller',
            \ALINA_PATH_TO_APP . '/mvc/Controller',
        ];

        foreach ($folders as $folder) {
            $res = \array_merge($res, Sys::getListFileToLowerCaseUniq($folder));
        }

        return \array_keys($res);
    }

    public static function getListFileToLowerCaseUniq(string $folder): array
    {
        $res   = [];
        $files = glob($folder . '/*.php');

        foreach ($files as $file) {
            $fileName       = strtolower(basename($file, '.php'));
            $res[$fileName] = $fileName;
        }

        return $res;
    }
}
