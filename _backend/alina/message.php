<?php
//ToDo: SIMPLIFY IT!!!
// ToDo: Think when to use static::removeById($message->id);

namespace alina;

use alina\utils\Data;

class message
{
    const MESSAGES = 'ALINA_MESSAGES';
    #region Facade (Collection)
    /**
     * @property array
     * Contains array of \alina\message objects
     **/
    static protected $collection              = [];
    static public    $flagCollectionInSession = FALSE;

    /**
     * @param $text
     * @param array $params
     * @param string $status
     * @see https://getbootstrap.com/docs/4.0/components/alerts/
     * alert alert-primary
     * alert alert-secondary
     * alert alert-success
     * alert alert-danger
     * alert alert-warning
     * alert alert-info
     * alert alert-light
     * alert alert-dark
     *
     * @return message
     */
    static public function set($text, $params = [], $status = 'alert alert-success')
    {
        $_this                 = new static;
        $_this->templateString = $text;
        $_this->params         = $params;
        $_this->status         = $status;
        $_this->isShown        = FALSE;
        $_this->addToCollection();

        return $_this;
    }

    static public function returnAllHtmlString()
    {
        $collection = static::getCollection();

        $all = '';
        /** @var \alina\message $message */
        foreach ($collection as $pseudoId => $message) {
            if (!$message->isShown) {
                $all              .= $message->messageHtml();
                $message->isShown = TRUE;
            }
        }

        return $all;
    }

    static public function returnAllMessages()
    {
        $collection = static::getCollection();
        $all        = [];
        /** @var \alina\message $message */
        foreach ($collection as $pseudoId => $message) {
            if (!$message->isShown) {
                $all[]            = [
                    'text'   => $message->messageRawText(),
                    'status' => $message->status,
                    'id'     => $message->id,
                ];
                $message->isShown = TRUE;
                static::removeById($message->id);
            }
        }

        //static::removeAll();
        return $all;
    }

    static protected function getCollection()
    {
        if (\alina\session::has(static::MESSAGES)) {
            static::$collection              = \alina\session::get(static::MESSAGES);
            static::$flagCollectionInSession = TRUE;
        } else {
            static::$flagCollectionInSession = FALSE;
        }

        return static::$collection;
    }

    static protected function setCollectionToSession()
    {
        try {
            if (\alina\session::set(static::MESSAGES, static::$collection)) {
                static::$flagCollectionInSession = TRUE;
            }
        } catch (\Exception $e) {
            error_log(__FUNCTION__, 0);
            error_log('Alina Messages are not in session!', 0);
            static::$flagCollectionInSession = FALSE;
        }
    }

    static public function removeAll()
    {
        static::$collection = static::getCollection();
        static::$collection = [];
        static::setCollectionToSession();
    }

    static public function removeById($id)
    {
        static::$collection = static::getCollection();
        if (array_key_exists($id, static::$collection)) {
            unset(static::$collection[$id]);
            static::setCollectionToSession();

            return TRUE;
        }

        return FALSE;
    }
    #endregion Facade (Collection)

    #region Message Object
    public $id;
    public $templateString = '';
    public $params         = [];
    public $messageRawText = '';
    public $status         = 'alert alert-success';
    public $statuses       = ['green', 'yellow', 'red'];
    public $isShown        = FALSE;

    public function addToCollection()
    {
        static::$collection   = static::getCollection();
        static::$collection[] = $this;
        $this->id             = \alina\utils\Arr::lastArrayKey(static::$collection);
        static::setCollectionToSession();
    }

    public function messageRawText()
    {
        if (Data::isIterable($this->templateString)) {
            $this->templateString = Data::hlpGetBeautifulJsonString($this->templateString);
        }
        $this->messageRawText = vsprintf($this->templateString, $this->params);
        //$this->messageRawText = var_export($this->params, 1);

        return $this->messageRawText;
    }

    public function messageHtml()
    {
        return \alina\utils\Sys::template(ALINA_PATH_TO_FRAMEWORK . '/mvc/template/_system/html/message.php', $this);
    }
    #endregion Message Object
}
