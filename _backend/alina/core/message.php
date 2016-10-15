<?php
namespace alina\core;

class message
{
    const MESSAGES = 'ALINA_MESSAGES';

    #region Fasade (Collection)
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

    static public function returnAllHtmlString()
    {
        $collection = static::getCollection();

        $all = '';
        /** @var \alina\core\message $message */
        foreach ($collection as $pseudoId => $message) {
            if (!$message->isShown) {
                $all .= $message->messageHtml();
                $message->isShown = TRUE;
            }
        }

        return $all;
    }

    static public function returnAllJsonString()
    {
        $collection = static::getCollection();

        $all = [];
        /** @var \alina\core\message $message */
        foreach ($collection as $pseudoId => $message) {
            if (!$message->isShown) {
                $all[]            = [
                    'text'   => $message->messageRawText(),
                    'status' => $message->status,
                    'id'     => $message->id,
                ];
                $message->isShown = TRUE;
            }
        }

        return json_encode($all);
    }

    static public function getCollection()
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

    static protected function setCollectionToSession()
    {
        try {
            if (\alina\core\session::set(static::MESSAGES, static::$collection))
                static::$flagCollectionInSession = TRUE;
        }
        catch (\Exception $e) {
            static::$flagCollectionInSession = FALSE;
        }
    }

    static public function removeAll(){
        static::$collection = static::getCollection();
        static::$collection = [];
        static::setCollectionToSession();
    }

    static public function removeById($id){
        static::$collection = static::getCollection();
        foreach (static::$collection as $pseudoId => $message) {
            if ($message->id == $id) {
                unset(static::$collection[$pseudoId]);
                static::setCollectionToSession();
                return TRUE;
            }
        }
        return FALSE;
    }
    #endregion Fasade (Collection)

    #region Message Object
    public $id;
    public $text     = '';
    public $params   = [];
    public $status   = 'green';
    public $statuses = ['green', 'yellow', 'red'];
    public $isShown  = FALSE;

    public function addToCollection()
    {
        static::$collection   = static::getCollection();
        static::$collection[] = $this;
        $lastId               = end(static::$collection);
        $lastId               = key(static::$collection);
        $this->id             = $lastId;
        static::setCollectionToSession();
    }

    public function messageRawText()
    {
        return vsprintf($this->text, $this->params);
    }

    public function messageHtml()
    {
        return template(PATH_TO_ALINA_BACKEND_DIR . '/core/mvc/template/_system/message.php', $this);
    }
    #endregion Message Object
}