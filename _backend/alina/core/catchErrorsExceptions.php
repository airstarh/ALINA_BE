<?php

namespace alina\core;


class catchErrorsExceptions
{
    static protected $instance = null;
    protected $exit = false;
    protected $message = '';

    /** @var array
     *
     * 'error_level' => string,
     * 'errstr' => string,
     * 'errfile' => string,
     * 'errline' => string,
     * 'error_trace' => string,
     */
    protected $message_params = array();

    protected function __construct()
    {

    }

    /**
     * $return static object
     */
    static public function obj()
    {
        if (null === static::$instance) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function error($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_STRICT:
                $error_level = "NOTICE";
                break;

            case E_WARNING:
            case E_USER_WARNING:
                $error_level = "WARNING";
                break;

            case E_ERROR:
            case E_USER_ERROR:
                $error_level = "FATAL";
                $this->exit = true;
                break;

            default:
                $error_level = "UNKNOWN";
                $this->exit = true;
        }

        $this->message = 'Error handled: %s, %s, in %s at line %d.';
        $this->message_params = array(
            'error_level' => $error_level,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
            //'error_trace' => string,
        );
        $this->parse_error();
    }

    public function exception($exception)
    {
        $error_message = method_exists($exception, 'getMessage')
            ? $exception->getMessage()
            : 'Unknown error';

        $error_file = method_exists($exception, 'getFile')
            ? $exception->getFile()
            : 'Unknown place';

        $error_line = method_exists($exception, 'getLine')
            ? $exception->getLine()
            : -1;

        $error_trace = method_exists($exception, 'getTraceAsString')
            ? $exception->getTraceAsString()
            : 'Trace is unavailable';

        $this->message = 'Exception thrown: %s, %s, in %s at line %d. Trace: '.PHP_EOL.'%s';
        $this->message_params = array(
            'error_level' => get_class($exception) . ' class',
            'errstr' => $error_message,
            'errfile' => $error_file,
            'errline' => $error_line,
            'error_trace' => $error_trace,
        );

        $this->parse_error();

        // In the case of exception, all render functions are stopped.
        // Thus it is good way to redirect user to the special page,
        // where all eerror messages are visible.
        // ToDo: may be a condition is needed.
        \base\router::redirect('/controller/SystemException');
    }

    public function parse_error()
    {
        $appDebugSettings = \base\application::getConfig('debug');
        if (in_array(true, $appDebugSettings)) {

            $error_message =  vsprintf($this->message, $this->message_params);
            $additional_info = (isset($this->message_params['error_trace']))
                ? $this->message_params['error_trace']
                : '' ;

            if ($appDebugSettings['toDb']) {
                debug::toDb(
                    $error_message,
                    $this->message_params['error_level'],
                    $additional_info
                );
            }
            if ($appDebugSettings['toPage']) {
                $errString = '';
                $errString .= $this->message_params['error_level'].'<br/>';
                $errString .= $this->message_params['errstr'].'<br/>';
                $errString .= $this->message_params['errfile'].'<br/>';
                $errString .= $this->message_params['errline'].'<br/>';
                if (isset($this->message_params['error_trace']) && !empty($this->message_params['error_trace'])) {
                    $errString .= $this->message_params['error_trace'].'<br/>';
                }
                $errString .= '<br/>';

                \base\model\message::set(
                    $errString,
                    'error'
                );
            }
        }

        $this->message = '';
        $this->message_params = '';

        if ($this->exit) {
            $this->exit = false;
            exit();
        }
    }
}