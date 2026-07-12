<?php

namespace alina\Utils;

use alina\traits\Singleton;
use stdClass;

class Request
{
    use Singleton;

    public $DOMAIN;
    public $URL_NATIVE;
    public $URL_PATH;
    public $METHOD;
    public $AJAX = false;
    public $REFERAL;
    public $QUERY_STRING;
    public $IP;
    public $BROWSER;
    public $BROWSER_enc;
    public $GET;
    public $POST;
    public $HEADERS;
    public $SERVER;
    public $COOKIE;
    public $FILES;
    public $R;

    protected function __construct()
    {
        $this->HEADERS = Data::toObject(getallheaders());
        $this->GET     = Sys::resolveGetDataAsObject();
        $this->POST    = Sys::resolvePostDataAsObject();
        $this->COOKIE  = Data::toObject($_COOKIE ?? []);
        $this->FILES   = Data::toObject($_FILES ?? []);
        $this->SERVER  = Data::toObject($_SERVER ?? []);
        $this->R       = Data::toObject($_REQUEST ?? []);
        /**
         * ATTENTION: cannot be defined here since USER constructor is referred to this constructor. RECURSION!!!
         */
        //$this->USER     = CurrentUser::obj()->attributes();
    }

    public function firstStep()
    {
        $this->AJAX         = Sys::isAjax();
        $this->METHOD       = Sys::getReqMethod() ?? 'CLI';
        $this->IP           = Sys::getUserIp();
        $this->REFERAL      = $_SERVER['HTTP_REFERER'] ?? '';
        $this->DOMAIN       = $_SERVER['HTTP_HOST']    ?? 'CLI';
        $this->URL_NATIVE   = $_SERVER['REQUEST_URI']  ?? 'CLI';
        $this->URL_PATH     = Url::cleanPath($_SERVER['REQUEST_URI'] ?? 'CLI');
        $this->QUERY_STRING = urldecode($_SERVER['QUERY_STRING'] ?? 'CLI');
        $this->BROWSER      = Sys::getUserBrowser();
        $this->BROWSER_enc  = Browser::hash($this->BROWSER);

        //ToDO: invoke get_browser()
        return $this;
    }

    ##################################################
    #region Facade

    public function tryHeader($headerName, $default = null, &$val = null)
    {
        $val = Obj::getValByPropNameCaseInsensitive($headerName, $this->HEADERS, $default);

        return $val;
    }

    public function isPost(&$post = null)
    {
        $is = $this->METHOD === 'POST';

        if ($is) {
            $post = $this->POST;
        }

        return $is;
    }

    public function isPut(&$post = null)
    {
        $is = static::obj()->METHOD === 'PUT';

        if ($is) {
            $post = static::obj()->POST;
        }

        return $is;
    }

    public function isDelete(&$post)
    {
        $is = static::obj()->METHOD === 'DELETE';

        if ($is) {
            $post = static::obj()->POST;
        }

        return $is;
    }

    public function isGet(&$get = null)
    {
        $is = $this->METHOD === 'GET';

        if ($is) {
            $get = $this->GET;
        }

        return $is;
    }

    public function isPostPutDelete(&$post = null)
    {
        $is = $this->isPost($post);

        if ($is) {
            return $is;
        }

        $is = $this->isPut($post);

        if ($is) {
            return $is;
        }

        $is = $this->isDelete($post);

        return $is;
    }

    /**
     * ;)
     * This is implemented to workaround COPY ON THE FLY process.
     * When we copy a Model with UNIQUE fields.
     * xD xD xD
     */
    public function lieThatPost($data = [])
    {
        if ($data) {
            $_POST      = (array) $data;
            $this->POST = (object) $data;
        }
        else {
            $_POST      = [];
            $this->POST = new stdClass();
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->METHOD              = 'POST';

        return $this->POST;
    }

    public function resetToGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->METHOD              = 'GET';
        $_POST                     = [];
        $this->POST                = new stdClass();
        $_FILES                    = [];
        $this->FILES               = new stdClass();
    }

    public function has($key, &$value = null)
    {
        $is = property_exists($this->R, $key);

        if ($is) {
            $value = $this->R->{$key};
        }

        return $is;
    }

    public function server(string $key): string
    {
        return $this->SERVER->{$key} ?? "CLI";
    }
    #endregion Facade
    ##################################################
}
