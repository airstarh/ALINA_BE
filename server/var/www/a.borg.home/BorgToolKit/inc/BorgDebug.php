<?php

define('BORG_MICROTIME', $_SERVER['REQUEST_TIME_FLOAT'] ?: microtime(TRUE));

class BorgDebug
{
    ##################################################
    private static string $fPath;

    static protected function fPath(string $fPath = null)
    {
        if ($fPath) {
            static::$fPath = $fPath;
        }

        if (empty(static::$fPath)) {
            static::$fPath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'DEBUG.html';
        }

        return static::$fPath;
    }


    static public function fDebug($data, $flags = FILE_APPEND, $fPath = null, string $transform = null): void
    {
        static $flagStarted = [];

        $fPath = static::fPath($fPath);

        switch ($transform) {
            case 'json':
                $output = static::hlpGetBeautifulJsonString($data);
                $fPath  = $fPath . '.yaml';
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
                ob_implicit_flush(FALSE);
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

        if (empty($flagStarted[$fPath])) {
            file_put_contents($fPath, PHP_EOL, 0);
            $flagStarted[$fPath] = true;
        }
        file_put_contents($fPath, $output, $flags);
        file_put_contents($fPath, PHP_EOL . PHP_EOL, FILE_APPEND);
    }

    static public function dDebug($data)
    {
        ob_start();
        ob_implicit_flush(FALSE);
        echo BorgTemplate::allCss();
        echo BorgTemplate::allJs();
        echo PHP_EOL;
        echo '<pre>';
        echo PHP_EOL;
        static::dump($data);
        echo PHP_EOL;
        echo '</pre>';
        echo PHP_EOL;
        $output = ob_get_clean();
        echo $output;
        exit();
    }

    static public function dump($data)
    {
        try {
            echo var_export($data, 1);
        } catch (ErrorException $e) {
            print_r($data);
        }
    }

    static public function checkExecution($data)
    {
        $data = self::hlpGetBeautifulJsonString($data);
        $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'DEBUG.txt';
        file_put_contents($path, $data);
    }

    static public function buffer($callback, ...$params)
    {
        ob_start();
        ob_implicit_flush(FALSE);
        call_user_func($callback, $params);
        $output = ob_get_clean();

        return $output;
    }

    static public function returnPrintR($data)
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
    static public function getMicroTimeDifferenceFromNow($microtime)
    {
        return microtime(TRUE) - $microtime;
    }

    static public function reportSpentTime($prepend = [], $append = [])
    {
        $main = [
            number_format(static::getMicroTimeDifferenceFromNow(BORG_MICROTIME), 10, '.', ' '),
            "SPENT",
            $_SERVER['SERVER_ADDR'],
            $_SERVER['REQUEST_URI'] ?? 'UNKNOWN REQUEST_URI',
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
    static public function hlpGetBeautifulJsonString($s)
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (is_array($s) || is_object($s)) {
            return json_encode($s, $flags);
        }
        if (static::isStringValidJson($s, $res)) {
            return json_encode($res, $flags);
        } else {
            return $s;
        }
    }

    static public function isStringValidJson($string, &$strJsonDecoded = null)
    {
        try {
            $strJsonDecoded = json_decode((string)$string, false, 512);
            return (json_last_error() === JSON_ERROR_NONE);
        } // Executed only in PHP 7, will not match in PHP 5
        catch (\Throwable  $exception) {
            return false;
        } // Executed only in PHP 5, will not be reached in PHP 7
        catch (\Exception $exception) {
            return false;
        }
    }

    static public function toObject($v)
    {
        if (!isset($v) || empty($v)) {
            return new \stdClass();
        }
        if (is_object($v)) {
            return $v;
        }
        if (is_array($v)) {
            // ToDo: Make less heavy
            return json_decode(json_encode($v), false);
        }
        if (is_string($v)) {
            $res = json_decode($v);
            if (json_last_error() == JSON_ERROR_NONE) {
                return $res;
            }
        }

        //throw new \Exception('Unable to convert to object');
        return (object)[];
    }

    ##################################################
}
