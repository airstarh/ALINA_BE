<?php

namespace alina;

use alina\traits\Msg;

class Message
{
    use Msg;
    public const MSG_KEY           = 'ALINA_MESSAGES';
    public static $MESSAGE_GET_KEY = 'alinamsg';
}
