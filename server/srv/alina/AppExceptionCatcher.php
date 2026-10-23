<?php

namespace alina;

use alina\Utils\Data;
use alina\Utils\Request;
use alina\Utils\Sys;
use alina\Utils\Url;
use ErrorException;
use Exception;
use Throwable;

class AppExceptionCatcher
{
    protected static bool $handlingException = false;

    protected $expClassName = '';
    protected $eSeverity    = '';
    protected $eCode        = '';
    protected $eString      = '';
    protected $eFile        = '';
    protected $eLine        = '';
    protected $eTrace       = '';

    protected function __construct()
    {
    }

    /**
     * @return static object
     */
    public static function obj()
    {
        return new static();
    }

    public function error($strErrLevelExpSeverity, $eString, $eFile, $eLine, $eContext = null)
    {
        //AlinaResponseSuccess(0);
        if (! (error_reporting() & $strErrLevelExpSeverity)) {
            // This error code is not included in error_reporting
            return;
        }

        throw new ErrorException($eString, 0, $strErrLevelExpSeverity, $eFile, $eLine);
    }

    /**
     * @param Throwable $objException
     * @param bool $forceExit
     * @throws Exception
     */
    public function exception($objException, $forceExit = true)
    {
        if (static::$handlingException) {
            $this->logSecondaryFailure('recursive exception handler invocation', $objException);

            if ($forceExit) {
                $this->renderBootstrapSafeError();
            }

            return null;
        }

        static::$handlingException = true;

        try {
            $this->captureException($objException);

            $this->runProtectedStage('prepare application error response', static function () {
                AlinaResponseSuccess(0);
            });

            ##################################################
            #region Clean buffer
            # @link https://stackoverflow.com/a/22069460/3142281
            $level = ob_get_level();

            while (@ob_end_clean()) {
                $level--;
            }
            #endregion Clean buffer
            ##################################################

            $this->runProtectedStage('roll back active transaction', static function () {
                mvc\Model\_baseAlinaEloquentTransaction::rollback();
            });

            $errorWasProcessed = $this->runProtectedStage('process application error', function () {
                $this->processError();
            });

            if (! $errorWasProcessed) {
                $this->logOriginalException();
            }

            if (! empty($_REQUEST['route_plan_b'])) {
                $redirected = $this->runProtectedStage('redirect after application error', static function () {
                    $R   = (object)$_REQUEST;
                    $url = $R->route_plan_b;
                    Data::sanitizeOutputObj($R);
                    $url = Url::addGetFromObject($url, $R);
                    Sys::redirect($url, 303);
                });

                if ($redirected) {
                    exit();
                }
            }

            if ($forceExit) {
                $exceptionPageResult = null;
                $rendered            = $this->runProtectedStage('render application exception page', function () use (&$exceptionPageResult) {
                    if (Request::obj()->isPostPutDelete()) {
                        $_POST                     = [];
                        $_FILES                    = [];
                        $_SERVER['REQUEST_METHOD'] = 'GET';
                        Request::obj()->METHOD     = 'GET';
                        Request::obj()->POST       = [];
                        Request::obj()->FILES      = [];
                    }

                    $exceptionPageResult = Alina()->mvcGo('Root', 'Exception', $this);
                });

                if (! $rendered) {
                    $this->renderBootstrapSafeError();
                }

                return $exceptionPageResult;
            }
        }
        finally {
            static::$handlingException = false;
        }

        return null;
    }

    protected function captureException($objException): void
    {
        $strUNKNOWN         = 'UNKNOWN';
        $this->expClassName = is_object($objException) ? get_class($objException) : gettype($objException);
        $this->eSeverity    = is_object($objException) && method_exists($objException, 'getSeverity')
            ? $objException->getSeverity()
            : $strUNKNOWN;
        $this->eCode = is_object($objException) && method_exists($objException, 'getCode')
            ? $objException->getCode()
            : $strUNKNOWN;
        $this->eString = is_object($objException) && method_exists($objException, 'getMessage')
            ? $objException->getMessage()
            : $strUNKNOWN;
        $this->eFile = is_object($objException) && method_exists($objException, 'getFile')
            ? $objException->getFile()
            : $strUNKNOWN;
        $this->eLine = is_object($objException) && method_exists($objException, 'getLine')
            ? $objException->getLine()
            : $strUNKNOWN;
        $this->eTrace = is_object($objException) && method_exists($objException, 'getTraceAsString')
            ? $objException->getTraceAsString()
            : $strUNKNOWN;
    }

    protected function runProtectedStage(string $stage, callable $callback): bool
    {
        try {
            $callback();

            return true;
        }
        catch (Throwable $secondaryException) {
            $this->logSecondaryFailure($stage, $secondaryException);

            return false;
        }
    }

    protected function logSecondaryFailure(string $stage, $secondaryException): void
    {
        $secondaryClass   = is_object($secondaryException) ? get_class($secondaryException) : gettype($secondaryException);
        $secondaryMessage = is_object($secondaryException) && method_exists($secondaryException, 'getMessage')
            ? $secondaryException->getMessage()
            : 'UNKNOWN';

        error_log(sprintf(
            'Exception handler stage "%s" failed with %s: %s. Original exception: %s: %s in %s:%s',
            $stage,
            $secondaryClass,
            $secondaryMessage,
            $this->expClassName ?: 'UNKNOWN',
            $this->eString ?: 'UNKNOWN',
            $this->eFile ?: 'UNKNOWN',
            $this->eLine ?: 'UNKNOWN',
        ));
    }

    protected function logOriginalException(): void
    {
        error_log(PHP_EOL . 'START ##################################################', 0);
        error_log($this->strMessage(), 0);
        error_log('END ##################################################' . PHP_EOL, 0);
    }

    protected function renderBootstrapSafeError(): void
    {
        if (! headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
        }

        echo 'Internal Server Error';
    }

    protected function processError()
    {
        $eMsg = $this->strMessage();

        #region PHP ERROR LOG
        error_log(PHP_EOL, 0);
        error_log('START ##################################################', 0);
        error_log($eMsg, 0);
        error_log('END ##################################################', 0);
        error_log(PHP_EOL, 0);
        #endregion PHP ERROR LOG

        $dbgCfg = AlinaCfg('debug');

        if (in_array(true, $dbgCfg)) {
            if (isset($dbgCfg['toDb']) && $dbgCfg['toDb']) {
                try {
                    Watcher::obj()->answer([
                        'error_class'    => $this->expClassName,
                        'error_severity' => $this->getSeverityStr(),
                        'error_code'     => $this->eCode,
                        'error_text'     => $this->eString,
                        'error_file'     => $this->eFile,
                        'error_line'     => $this->eLine,
                        'error_trace'    => $this->eTrace,
                    ]);
                }
                catch (Exception $e) {
                    error_log('Was unable to write Error to db!!!');
                    error_log($e->getMessage());
                }
            }

            if (isset($dbgCfg['toPage']) && $dbgCfg['toPage']) {
                Message::setDanger('Provide this number to your Admin: %s', [(string) Watcher::obj()->id()]);
                Message::setDanger($this->eString, [(string) Watcher::obj()->id()]);
                MessageAdmin::setDanger($eMsg);
            }

            if (isset($dbgCfg['toFile']) && $dbgCfg['toFile']) {
                $NL = PHP_EOL . '<br>' . PHP_EOL;
                Sys::fDebug($this->strMessage($NL), true);
            }
        }
    }

    protected function strMessage($NL = PHP_EOL)
    {
        $arrMessage                  = [];
        $strMessage                  = '';
        $arrMessage['IP...........'] = Sys::getUserIp();
        $arrMessage['DOMAIN.......'] = $_SERVER['HTTP_HOST'] ?? 'CLI';
        $arrMessage['URL_PATH.....'] = Url::cleanPath($_SERVER['REQUEST_URI'] ?? 'CLI');
        $arrMessage['CLASS........'] = $this->expClassName;
        $arrMessage['SEVERITY.....'] = $this->getSeverityStr();
        $arrMessage['CODE.........'] = $this->eCode;
        $arrMessage['FILE.........'] = $this->eFile;
        $arrMessage['LINE.........'] = $this->eLine;
        $arrMessage['TEXT.........'] = $this->eString;
        $arrMessage['TRACE........'] = $this->eTrace;

        foreach ($arrMessage as $k => $v) {
            if (in_array($k, ['TRACE........', 'TEXT.........'])) {
                $strMessage .= "{$NL}{$k}:{$NL}{$v}{$NL}{$NL}";
            }
            else {
                $strMessage .= "{$k}{$v}{$NL}";
            }
        }

        return $strMessage;
    }

    protected function getSeverityStr()
    {
        $cnstnts = get_defined_constants();
        $str     = array_search($this->eSeverity, $cnstnts);
        $str     = $str ?: $this->eSeverity;

        return $str;
    }
}

class WarningException extends ErrorException
{
}

class ParseException extends ErrorException
{
}

class NoticeException extends ErrorException
{
}

class CoreErrorException extends ErrorException
{
}

class CoreWarningException extends ErrorException
{
}

class CompileErrorException extends ErrorException
{
}

class CompileWarningException extends ErrorException
{
}

class UserErrorException extends ErrorException
{
}

class UserWarningException extends ErrorException
{
}

class UserNoticeException extends ErrorException
{
}

class StrictException extends ErrorException
{
}

class RecoverableErrorException extends ErrorException
{
}

class DeprecatedException extends ErrorException
{
}

class UserDeprecatedException extends ErrorException
{
}
