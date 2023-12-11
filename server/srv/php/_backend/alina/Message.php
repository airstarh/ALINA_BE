<?php

namespace alina;

use alina\traits\Msg;
use alina\Utils\Arr;
use alina\Utils\Data;

class Message
{
    use Msg;
    const MSG_KEY = 'ALINA_MESSAGES';
    static public $MESSAGE_GET_KEY = 'alinamsg';
}
