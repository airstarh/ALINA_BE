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
    public $isShown  = FALSE;

    /**
     * @property array
     * Contains array of \alina\core\message objects
     **/
    static public $collection                = [];
    static public $flagCollectionInSession   = FALSE;
    static public $flagCollectionHasMessages = FALSE;

    static public function set($text, $params = [], $status = 'green')
    {
        $_this          = new static;
        $_this->text    = $text;
        $_this->params  = $params;
        $_this->status  = $status;
        $_this->isShown = FALSE;
        $_this->addToCollection();
    }

    public function getCollection()
    {
        try {
            if (\alina\core\session::has(static::MESSAGES)) {
                static::$collection              = \alina\core\session::get(static::MESSAGES);
                static::$flagCollectionInSession = TRUE;
            }
            else {
                static::$flagCollectionInSession = FALSE;
            }
        }
        catch (\Exception $e) {
            static::$flagCollectionInSession = FALSE;
        }

        return static::$collection;
    }

    protected function setCollectionToSession()
    {
        try {
            if (\alina\core\session::set(static::MESSAGES, static::$collection))
                static::$flagCollectionInSession = TRUE;
        }
        catch (\Exception $e) {
            static::$flagCollectionInSession = FALSE;
        }
    }

    public function addToCollection()
    {
        static::$collection   = $this->getCollection();
        static::$collection[] = $this;
        $lastId               = end(static::$collection);
        $lastId               = key(static::$collection);
        $this->id             = $lastId;
        $this->setCollectionToSession();
    }
}