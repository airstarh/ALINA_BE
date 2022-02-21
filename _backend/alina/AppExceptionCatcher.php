<?php

namespace alina;

use alina\mvc\model\error_log;
use alina\utils\Data;
use alina\utils\Sys;
use alina\utils\Url;

class AppExceptionCatcher
{
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
    static public function obj()
    {
        return new static;
    }

    public function error($strErrLevelExpSeverity, $eString, $eFile, $eLine, $eContext)
    {
        //AlinaResponseSuccess(0);
        if (!(error_reporting() & $strErrLevelExpSeverity)) {
            // This error code is not included in error_reporting
            return;
        }
        throw new \ErrorException ($eString, 0, $strErrLevelExpSeverity, $eFile, $eLine);
    }

    /**
     * @param \Exception $objException
     * @param bool $forceExit
     * @throws \Exception
     */
    public function exception($objException, $forceExit = TRUE)
    {
        AlinaResponseSuccess(0);
        ##################################################
        #region Clean buffer
        # @link https://stackoverflow.com/a/22069460/3142281
        $level = ob_get_level();
        while (@ob_end_clean()) {
            $level--;
        }
        #endregion Clean buffer
        ##################################################
        \alina\mvc\model\_baseAlinaEloquentTransaction::rollback();
        ##################################################
        $strUNKNOWN         = 'UNKNOWN';
        $this->expClassName = get_class($objException);
        $this->eSeverity    = method_exists($objException, 'getSeverity')
            ? $objException->getSeverity()
            : $strUNKNOWN;
        $this->eCode        = method_exists($objException, 'getCode')
            ? $objException->getCode()
            : $strUNKNOWN;
        $this->eString      = method_exists($objException, 'getMessage')
            ? $objException->getMessage()
            : $strUNKNOWN;
        $this->eFile        = method_exists($objException, 'getFile')
            ? $objException->getFile()
            : $strUNKNOWN;
        $this->eLine        = method_exists($objException, 'getLine')
            ? $objException->getLine()
            : $strUNKNOWN;
        $this->eTrace       = method_exists($objException, 'getTraceAsString')
            ? $objException->getTraceAsString()
            : $strUNKNOWN;
        ##################################################
        $this->processError();
        ##################################################
        if (isset($_REQUEST['route_plan_b']) && !empty($_REQUEST['route_plan_b'])) {
            $R   = (object)$_REQUEST;
            $url = $R->route_plan_b;
            Data::sanitizeOutputObj($R);
            $url = Url::addGetFromObject($url, $R);
            Sys::redirect($url, 303);
        } elseif ($forceExit) {
            Alina()->mvcGo('root', 'Exception', $this);
        }
        ##################################################
    }

    protected function processError()
    {
        #region PHP ERROR LOG
        error_log(json_encode($this->strMessage()), 0);
        #endregion PHP ERROR LOG
        $dbgCfg = AlinaCfg('debug');
        if (in_array(TRUE, $dbgCfg)) {
            if (isset($dbgCfg['toDb']) && $dbgCfg['toDb']) {
                try {
                    $mEL = new error_log();
                    $mEL->insert([
                        'error_class'    => $this->expClassName,
                        'error_severity' => $this->getSeverityStr(),
                        'error_code'     => $this->eCode,
                        'error_text'     => $this->eString,
                        'error_file'     => $this->eFile,
                        'error_line'     => $this->eLine,
                        'error_trace'    => $this->eTrace,
                    ]);
                } catch (\Exception $e) {
                    error_log('Was unable to write Error to db!!!');
                    error_log($e->getMessage());
                }
            }
            if (isset($dbgCfg['toPage']) && $dbgCfg['toPage']) {
                Message::setDanger('¯\_(ツ)_/¯');
                MessageAdmin::setDanger($this->strMessage());
            }
            if (isset($dbgCfg['toFile']) && $dbgCfg['toFile']) {
                $NL = PHP_EOL . '<br>' . PHP_EOL;
                Sys::fDebug($this->strMessage($NL));
            }
        }
    }

    protected function strMessage($NL = PHP_EOL)
    {
        $arrMessage             = [];
        $strMessage             = '';
        $arrMessage['IP']       = Sys::getUserIp();
        $arrMessage['URL_PATH'] = Url::cleanPath($_SERVER['REQUEST_URI']);
        $arrMessage['Class']    = $this->expClassName;
        $arrMessage['Severity'] = $this->getSeverityStr();
        $arrMessage['Code']     = $this->eCode;
        $arrMessage['Text']     = $this->eString;
        $arrMessage['File']     = $this->eFile;
        $arrMessage['Line']     = $this->eLine;
        $arrMessage['Trace']    = $this->eTrace;
        foreach ($arrMessage as $k => $v) {
            if ($k === 'Trace') {
                $strMessage .= "{$NL}{$k}:{$NL}{$v}{$NL}";
            } else {
                $strMessage .= "{$k}: {$v}{$NL}";
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

class WarningException extends \ErrorException
{
}

class ParseException extends \ErrorException
{
}

class NoticeException extends \ErrorException
{
}

class CoreErrorException extends \ErrorException
{
}

class CoreWarningException extends \ErrorException
{
}

class CompileErrorException extends \ErrorException
{
}

class CompileWarningException extends \ErrorException
{
}

class UserErrorException extends \ErrorException
{
}

class UserWarningException extends \ErrorException
{
}

class UserNoticeException extends \ErrorException
{
}

class StrictException extends \ErrorException
{
}

class RecoverableErrorException extends \ErrorException
{
}

class DeprecatedException extends \ErrorException
{
}

class UserDeprecatedException extends \ErrorException
{
}
