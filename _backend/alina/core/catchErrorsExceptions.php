<?php

namespace alina\core;

class catchErrorsExceptions
{
    static protected $instance = NULL;
    protected        $exit     = FALSE;
    protected        $message  = '';

    /** @var array
     *
     * 'error_level' => string,
     * 'errstr' => string,
     * 'errfile' => string,
     * 'errline' => string,
     * 'error_trace' => string,
     */
    protected $messageParams = [];

    public $eLevel  = '';
    public $eString = '';
    public $eFile   = '';
    public $eLine   = '';
    public $eTrace  = '';

    public $messageString = '';
    public $messageHtml   = '';

    protected function __construct()
    {

    }


    /**
     * $return static object
     */
    static public function obj()
    {
        if (NULL === static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function error($eLevel, $eString, $eFile, $eLine, $eContext)
    {
        throw new \ErrorException ($eString, 0, $eLevel, $eFile, $eLine);
    }

    public function exception($exception)
    {
        fDebug($exception);

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
        $this->message       = "Error! {$NL}Level: %s {$NL}Text: %s {$NL}File: %s  {$NL}Line: %d. {$NL}Trace: {$NL}%s ";
        $this->messageParams = [
            $this->eLevel,
            $this->eString,
            $this->eFile,
            $this->eLine,
            $this->eTrace,
        ];

        $this->prepareError();

        // ToDo: may be a condition is needed.
        \alina\app::get()->mvcGo('root', 'Exception', $this);
    }

    public function prepareError()
    {
        $this->messageString = vsprintf($this->message, $this->messageParams);
        \alina\core\message::set($this->message, $this->messageParams, 'red');

        $config = \alina\app::getConfig('debug');
        if (in_array(TRUE, $config)) {

            if (isset($config['toDb']) && $config['toDb']) {
                // ToDo: Save to DB.
            }

            if (isset($config['toPage']) && $config['toPage']) {
                // ToDo: Show on Page.
            }

            if (isset($config['toFile']) && $config['toFile']) {
                $this->messageHtml = '<br/>';
                $this->messageHtml .= $this->eLevel . '<br/>' . PHP_EOL;
                $this->messageHtml .= $this->eString . '<br/>' . PHP_EOL;
                $this->messageHtml .= $this->eFile . '<br/>' . PHP_EOL;
                $this->messageHtml .= $this->eLine . '<br/>' . PHP_EOL;
                $this->messageHtml .= $this->eTrace . '<br/>' . PHP_EOL;
                $this->messageHtml .= '<br/>';
                fDebug($this->messageHtml);
            }
        }

        if ($this->exit) {
            $this->exit = FALSE;
            exit();
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