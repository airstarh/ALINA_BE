<?php
//ToDo: SIMPLIFY IT!!!
// ToDo: Think when to use static::removeById($message->id);

namespace alina;

use alina\traits\Msg;
use alina\utils\Arr;
use alina\utils\Data;

class MessageAdmin
{
    use Msg;
    const MSG_KEY = 'ALINA_MESSAGES_ADMIN';
}
