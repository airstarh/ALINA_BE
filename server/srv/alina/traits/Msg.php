<?php

namespace alina\traits;

use alina\Message;
use alina\Utils\Arr;
use alina\Utils\Data;
use alina\Utils\Request;

trait Msg
{
    #region Facade (Collection)
    /**
     * @property array
     * Contains array of \alina\message objects
     **/
    static protected $collection    = [];
    static public    $statusClasses = [
        0 => 'alert alert-success',
        1 => 'alert alert-info',
        2 => 'alert alert-warning',
        3 => 'alert alert-danger',
    ];
    //static public    $MESSAGE_GET_KEY = 'alinamsg';

    /**
     * @param $text
     * @param array $params
     * @param integer $status
     * @return static
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
     */
    static protected function set($text, $params = [], $status = 1)
    {
        if (!is_string($text)) {
            $text = var_export($text, 1);
        }
        $_this                 = new static;
        $_this->templateString = $text;
        $_this->params         = $params;
        $_this->status         = $status;
        $_this->isShown        = FALSE;
        $_this->addToCollection();

        return $_this;
    }

    ###############
    #region Set
    static public function setSuccess($text, $params = [])
    {
        $status = 0;

        return static::set($text, $params, $status);
    }

    static public function setInfo($text, $params = [])
    {
        $status = 1;

        return static::set($text, $params, $status);
    }

    static public function setWarning($text, $params = [])
    {
        $status = 2;

        return static::set($text, $params, $status);
    }

    static public function setDanger($text, $params = [])
    {
        $status = 3;

        return static::set($text, $params, $status);
    }
    #rendegion Set
    ###############
    static public function fromRequest()
    {
        if (isset(Request::obj()->GET->{static::$MESSAGE_GET_KEY})) {
            try {
                $arr = Request::obj()->GET->{static::$MESSAGE_GET_KEY};
                static::addFromArray(json_decode($arr));
            } catch (\ErrorException $e) {
                static::setDanger('Message delivery problem');
            }
        }
    }

    static protected function addFromArray($arr)
    {
        foreach ($arr as $i => $msg) {
            static::set(
                $msg->text,
                [],
                $msg->status
            );
        }
    }

    static public function returnAllHtmlString()
    {
        $collection = static::getCollection();
        $all        = '';
        /** @var Message $msg */
        foreach ($collection as $pseudoId => $msg) {
            if (!$msg->isShown) {
                $all          .= $msg->messageHtml();
                $msg->isShown = TRUE;
                static::removeById($msg->id);
            }
        }

        return $all;
    }

    static public function returnAllMessages()
    {
        $collection = static::getCollection();
        $all        = [];
        /** @var Message $message */
        foreach ($collection as $pseudoId => $message) {
            if (!$message->isShown) {
                $all[]            = [
                    'text'           => $message->messageRawText(),
                    'status'         => $message->status,
                    'id'             => $message->id,
                    'params'         => $message->params,
                    'templateString' => $message->templateString,
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
        return static::$collection;
    }

    static public function removeAll()
    {
        static::$collection = [];
    }

    static public function removeById($id)
    {
        static::$collection = static::getCollection();
        if (array_key_exists($id, static::$collection)) {
            unset(static::$collection[$id]);

            return TRUE;
        }

        return FALSE;
    }
    #endregion Facade (Collection)
    ##################################################
    ##################################################
    ##################################################
    #region Message Object
    public $id;
    public $templateString = '';
    public $params         = [];
    public $messageRawText = '';
    public $status         = 0;
    public $isShown        = FALSE;

    protected function addToCollection()
    {
        static::$collection   = static::getCollection();
        static::$collection[] = $this;
        if (!isset($this->id) || empty($this->id)) {
            $this->id = Arr::lastArrayKey(static::$collection);
        }
    }

    public function messageRawText()
    {
        if (Data::isIterable($this->templateString)) {
            $this->templateString = Data::hlpGetBeautifulJsonString($this->templateString);
        }
        try {
            $this->messageRawText = vsprintf($this->templateString, $this->params);
        } catch (\Exception $e) {
            $this->messageRawText = '';
            $this->messageRawText .= PHP_EOL;
            $this->messageRawText .= '>>>';
            $this->messageRawText .= var_export($this->templateString, 1);
            $this->messageRawText .= PHP_EOL;
            $this->messageRawText .= '<<<>>>';
            $this->messageRawText .= PHP_EOL;
            $this->messageRawText .= var_export($this->params, 1);
            $this->messageRawText .= '<<<';
            $this->messageRawText .= PHP_EOL;
        }

        return $this->messageRawText;
    }

    public function messageHtml()
    {
        //ToDo: make for user application.
        return \alina\Utils\Sys::template(ALINA_PATH_TO_FRAMEWORK . '/mvc/template/_system/html/message.php', $this);
    }
    #endregion Message Object
}
