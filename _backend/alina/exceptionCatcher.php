<?php

namespace alina;

class exceptionCatcher
{
    protected $errorTemplate = '';
    /** @property array errorParams
     *
     * 'error_level' => string,
     * 'errstr' => string,
     * 'errfile' => string,
     * 'errline' => string,
     * 'error_trace' => string,
     */
    protected $errorParams = [];
    public    $eLevel      = '';
    public    $eString     = '';
    public    $eFile       = '';
    public    $eLine       = '';
    public    $eTrace      = '';
    public    $errprString = '';
    public    $errorHtml   = '';

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

    public function error($eLevel, $eString, $eFile, $eLine, $eContext)
    {
        if (!(error_reporting() & $eLevel)) {
            // This error code is not included in error_reporting
            return;
        }
        throw new \ErrorException ($eString, 0, $eLevel, $eFile, $eLine);
    }

    public function exception($exception)
    {
        $this->eLevel = get_class($exception);

        $this->eString = method_exists($exception, 'getMessage')
            ? $exception->getMessage()
            : 'Unknown error';

        $this->eFile = method_exists($exception, 'getFile')
            ? $exception->getFile()
            : 'Unknown place';

        $this->eLine = method_exists($exception, 'getLine')
            ? $exception->getLine()
            : -1;

        $this->eTrace        = method_exists($exception, 'getTraceAsString')
            ? $exception->getTraceAsString()
            : 'Trace is unavailable';
        $NL                  = PHP_EOL;
        $this->errorTemplate = "Error! {$NL}Level: %s {$NL}Text: %s {$NL}File: %s  {$NL}Line: %d. {$NL}Trace: {$NL}%s ";
        $this->errorParams   = [
            $this->eLevel,
            $this->eString,
            $this->eFile,
            $this->eLine,
            $this->eTrace,
        ];

        $this->prepareError();
        $this->processError();

        if (isAjax()) {
            (new \alina\mvc\view\json())->standardRestApiResponse();
        } else {
            ob_end_clean();
            \alina\app::get()->mvcGo('root', 'Exception', $this);
        }
    }

    public function prepareError()
    {
        $message = \alina\message::set($this->errorTemplate, $this->errorParams, 'alert alert-danger');
        $this->errprString = $message->messageRawText();
    }

    public function processError() {

        #region PHP ERROR LOG
        error_log('ERROR MESSAGE',0);
        error_log(json_encode($this->errprString),0);
        #endregion PHP ERROR LOG

        $config = \alina\app::getConfig('debug');
        if (in_array(TRUE, $config)) {

            if (isset($config['toDb']) && $config['toDb']) {
                // ToDo: Save to DB.
            }

            if (isset($config['toPage']) && $config['toPage']) {
                // ToDo: Show on Page.
            }

            if (isset($config['toFile']) && $config['toFile']) {
                $this->errorHtml = [];
                $this->errorHtml[]= $this->eLevel;
                $this->errorHtml[]= $this->eString;
                $this->errorHtml[]= $this->eFile;
                $this->errorHtml[]= $this->eLine;
                $this->errorHtml[]= $this->eTrace;
                $NL = '</br>'.PHP_EOL;
                $this->errorHtml = implode($NL, $this->errorHtml);
                fDebug($this->errorHtml);
            }
        }
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
