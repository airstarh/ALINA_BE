<?php

namespace alina\utils;

use alina\session;
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

    protected function __construct()
    {
        $this->DOMAIN       = Url::cleanDomain($_SERVER['HTTP_HOST']);
        $this->URL_PATH     = Url::cleanPath($_SERVER['REQUEST_URI']);
        $this->QUERY_STRING = $_SERVER['QUERY_STRING'];
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

    public function tryHeader($name)
    {
        return Obj::getValByPropNameCaseInsensitive($name, $this->HEADERS);
        //return Arr::getArrayValue($name, (array)$this->HEADERS);
    }
}
