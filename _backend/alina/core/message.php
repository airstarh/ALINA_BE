<?php
namespace alina\core;

class message
{
    const MESSAGES = 'ALINA_MESSAGES';

    public $id;
    public $text     = '';
    public $params   = [];
    public $status   = 'green';
    public $statuses = ['green', 'yellow', 'red'];
    public $isShown = FALSE;

    /**
     * @property array
     * Contains array of \alina\core\message objects
     **/
    static public $collection = [];
    static public $flagCollectionHasMessages = false;

    static public function set($text, $params = [], $status = 'green')
    {
        $_this         = new static;
        $_this->text   = $text;
        $_this->params = $params;
        $_this->status = $status;
        $_this->addToCollection();
    }

    public function addToCollection() {
        
    }

    public function parseHtml()
    {
        if (!static::$flagCollectionHasMessages) return FALSE;

        $messages = \alina\core\session::get(static::MESSAGES);

    }
}