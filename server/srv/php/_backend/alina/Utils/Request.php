<?php

namespace alina\Utils;

use alina\traits\Singleton;

class Request
{
    use Singleton;

    public $DOMAIN;
    public $URL_PATH;
    public $METHOD;
    public $AJAX;
    public $QUERY_STRING;
    public $IP;
    public $BROWSER;
    public $BROWSER_enc;
    public $LANGUAGE;
    public $GET;
    public $POST;
    public $HEADERS;
    public $SERVER;
    public $COOKIE;
    public $FILES;
    public $R;

    protected function __construct()
    {
        //ToDo: Security
        //ToDo: process fields
        $this->DOMAIN       = (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
        $this->URL_PATH     = Url::cleanPath($_SERVER['REQUEST_URI']);
        $this->QUERY_STRING = urldecode($_SERVER['QUERY_STRING']);
        $this->METHOD       = Sys::getReqMethod();
        $this->AJAX         = Sys::isAjax();
        $this->HEADERS      = Data::toObject(getallheaders());
        $this->GET          = Sys::resolveGetDataAsObject();
        $this->POST         = Sys::resolvePostDataAsObject();
        $this->IP           = Sys::getUserIp();
        $this->BROWSER      = Sys::getUserBrowser();
        $this->LANGUAGE     = Sys::getUserLanguage();
        $this->COOKIE       = Data::toObject($_COOKIE);
        $this->FILES        = Data::toObject($_FILES);
        $this->SERVER       = Data::toObject($_SERVER);
        $this->R            = Data::toObject($_REQUEST);
        #####
        $this->processBrowserData();
        /**
         * ATTENTION: cannot be defined here since USER constructor is referred to this constructor. RECURSION!!!
         */
        //$this->USER     = CurrentUser::obj()->attributes();
    }

    protected function processBrowserData()
    {
        $this->BROWSER_enc = Browser::hash($this->BROWSER);
        //ToDO: invoke get_browser()
    }

    public function TOTAL_DEBUG_DATA()
    {
        $res = [
            'DOMAIN'       => $this->DOMAIN,
            'URL_PATH'     => $this->URL_PATH,
            'QUERY_STRING' => $this->QUERY_STRING,
            'METHOD'       => $this->METHOD,
            'AJAX'         => $this->AJAX,
            'IP'           => $this->IP,
            'BROWSER'      => $this->BROWSER,
            'LANGUAGE'     => $this->LANGUAGE,
            'HEADERS'      => $this->HEADERS,
            'COOKIE'       => $this->COOKIE,
            'GET'          => $this->GET,
            'POST'         => $this->POST,
            'FILES'        => $this->FILES,
            'SERVER'       => $this->SERVER,
        ];

        return $res;
    }

    public function tryHeader($prop, &$val = null)
    {
        $val = Obj::getValByPropNameCaseInsensitive($prop, $this->HEADERS);

        return $val;
        //return Arr::getArrayValue($name, (array)$this->HEADERS);
    }

    ##################################################
    #region Facade
    static public function isPost(&$post = null)
    {
        $is = static::obj()->METHOD === 'POST';
        if ($is) {
            $post = static::obj()->POST;
        }

        return $is;
    }

    static public function isPut(&$post = null)
    {
        $is = static::obj()->METHOD === 'PUT';
        if ($is) {
            $post = static::obj()->POST;
        }

        return $is;
    }

    static public function isDelete(&$post)
    {
        $is = static::obj()->METHOD === 'DELETE';
        if ($is) {
            $post = static::obj()->POST;
        }

        return $is;
    }

    static public function isGet(&$get = null)
    {
        $is = static::obj()->METHOD === 'GET';
        if ($is) {
            $get = static::obj()->GET;
        }

        return $is;
    }

    static public function isPostPutDelete(&$post = null)
    {
        $is = static::isPost($post);
        if ($is) return $is;
        $is = static::isPut($post);
        if ($is) return $is;
        $is = static::isDelete($post);
        if ($is) return $is;

        return false;
    }

    /**
     * ;)
     * This is implemented to workaround COPY ON THE FLY process.
     * When we copy a Model with UNIQUE fields.
     * xD xD xD
     */
    static public function lieThatPost($data = [])
    {
        if ($data) {
            $_POST              = (array)$data;
            static::obj()->POST = (object)$data;
        } else {
            $_POST              = [];
            static::obj()->POST = (object)[];
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        static::obj()->METHOD      = 'POST';

        return static::obj()->POST;
    }

    static public function has($key, &$value = null)
    {
        $is = property_exists(static::obj()->R, $key);
        if ($is) {
            $value = static::obj()->R->{$key};
        }

        return $is;
    }
    #endregion Facade
    ##################################################
}
