<?php

namespace alina;

use alina\mvc\view\json;
use alina\utils\Request;
use alina\utils\Sys;
use alina\utils\Url;

class exceptionCatcher
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
        ##################################################
        #region Clean bugger
        # @link https://stackoverflow.com/a/22069460/3142281
        $level = ob_get_level();
        while (@ob_end_clean()) {
            $level--;
        }
        #endregion Clean bugger
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
        $this->processError();
        ##################################################
        if (Request::has('route_plan_b', $v)) {
            $url = Url::addGetFromObject($v, Request::obj()->R);
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

        $dbgCfg = app::getConfig('debug');
        if (in_array(TRUE, $dbgCfg)) {

            if (isset($dbgCfg['toDb']) && $dbgCfg['toDb']) {
                // ToDo: Save to DB.
            }

            if (isset($dbgCfg['toPage']) && $dbgCfg['toPage']) {
                MessageAdmin::setDanger($this->strMessage());
            }

            if (isset($dbgCfg['toFile']) && $dbgCfg['toFile']) {
                $NL = '</br>' . PHP_EOL;
                Sys::fDebug($this->strMessage($NL));
            }
        }
    }

    protected function strMessage($NL = PHP_EOL)
    {
        $arrMessage             = [];
        $strMessage             = '';
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
