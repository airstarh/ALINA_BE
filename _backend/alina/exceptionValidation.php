<?php

namespace alina;

class exceptionValidation extends \ErrorException
{
    public function __construct($message = "", $code = 0, $severity = 1, $filename = __FILE__, $lineno = __LINE__, $previous = NULL)
    {
        parent::__construct($message, $code, $severity, $filename, $lineno, $previous);
        $message = trim($message);
        message::set($message, [], 'alert alert-danger');
    }
}
