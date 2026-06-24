<?php

namespace alina\traits;

use alina\Message;
use alina\Utils\Arr;
use alina\Utils\Data;
use alina\Utils\Request;
use ErrorException;
use Exception;

trait Msg
{
    #region Facade (Collection)
    /**
     * @property array $collection
     * Contains array of \alina\message objects
     **/
    protected static array $collection = [];
    public static $statusClasses       = [
        0 => 'alert alert-success',
        1 => 'alert alert-info',
        2 => 'alert alert-warning',
        3 => 'alert alert-danger',
    ];
    //static public    $MESSAGE_GET_KEY = 'alinamsg';

    /**
     * @param $text
     * @param array $params
     * @param int $status
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
    protected static function set($text, $params = [], $status = 1)
    {
        if (! is_string($text)) {
            $text = var_export($text, 1);
        }
        $_this                 = new static();
        $_this->templateString = ___($text);
        $_this->params         = $params;
        $_this->status         = $status;
        $_this->isShown        = false;
        $_this->addToCollection();

        return $_this;
    }

    ###############
    #region Set
    public static function setSuccess($text, $params = [])
    {
        $status = 0;

        return static::set($text, $params, $status);
    }

    public static function setInfo($text, $params = [])
    {
        $status = 1;

        return static::set($text, $params, $status);
    }

    public static function setWarning($text, $params = [])
    {
        $status = 2;

        return static::set($text, $params, $status);
    }

    public static function setDanger($text, $params = [])
    {
        $status = 3;

        return static::set($text, $params, $status);
    }
    #rendegion Set
    ###############
    public static function fromRequest()
    {
        if (isset(Request::obj()->GET->{static::$MESSAGE_GET_KEY})) {
            try {
                $arr = Request::obj()->GET->{static::$MESSAGE_GET_KEY};
                static::addFromArray(json_decode($arr));
            }
            catch (ErrorException $e) {
                static::setDanger('Message delivery problem');
            }
        }
    }

    protected static function addFromArray($arr)
    {
        foreach ($arr as $i => $msg) {
            static::set(
                $msg->text,
                [],
                $msg->status
            );
        }
    }

    public static function returnAllDbData(): string
    {
        $collection = static::getCollection();
        $all        = [];

        /** @var Message $msg */
        foreach ($collection as $pseudoId => $msg) {
            if (! $msg->isShown) {
                $all[] = $msg->messageRawText();
                // $msg->isShown = true;
                // static::removeById($msg->id);
            }
        }

        return implode(PHP_EOL, $all);
    }

    public static function returnAllHtmlString()
    {
        $collection = static::getCollection();
        $all        = '';

        /** @var Message $msg */
        foreach ($collection as $pseudoId => $msg) {
            if (! $msg->isShown) {
                $all .= $msg->messageHtml();
                // $msg->isShown = true;
                // static::removeById($msg->id);
            }
        }

        return $all;
    }

    public static function returnAllMessages()
    {
        $collection = static::getCollection();
        $all        = [];

        /** @var Message $message */
        foreach ($collection as $pseudoId => $message) {
            if (! $message->isShown) {
                $all[] = [
                    'text'           => $message->messageRawText(),
                    'status'         => $message->status,
                    'id'             => $message->id,
                    'params'         => $message->params,
                    'templateString' => $message->templateString,
                ];
                // $message->isShown = true;
                // static::removeById($message->id);
            }
        }

        return $all;
    }

    protected static function getCollection()
    {
        return static::$collection;
    }

    public static function removeAll()
    {
        static::$collection = [];
    }

    public static function removeById($id)
    {
        static::$collection = static::getCollection();

        if (array_key_exists($id, static::$collection)) {
            unset(static::$collection[$id]);

            return true;
        }

        return false;
    }
    #endregion Facade (Collection)
    ##################################################
    #region Message Object
    public $id;
    public $templateString = '';
    public $params         = [];
    public $messageRawText = '';
    public $status         = 0;
    public $isShown        = false;

    protected function addToCollection()
    {
        static::$collection   = static::getCollection();
        static::$collection[] = $this;

        if (empty($this->id)) {
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
        }
        catch (Exception $e) {
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
