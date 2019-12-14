<?php

namespace alina\utils;

use alina\traits\Singleton;

class Request
{
    use Singleton;
    public $DOMAIN;
    public $URL_PATH;
    public $METHOD;
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
    public $SESSION;
    public $FILES;

    protected function __construct()
    {
        $this->DOMAIN       = $_SERVER['HTTP_HOST'];
        $this->URL_PATH     = Url::cleanPath($_SERVER['REQUEST_URI']);
        $this->QUERY_STRING = $_SERVER['QUERY_STRING'];
        $this->METHOD       = Sys::getReqMethod();
        $this->HEADERS      = Data::toObject(getallheaders());
        $this->GET          = Sys::resolveGetDataAsObject();
        $this->POST         = Sys::resolvePostDataAsObject();
        $this->IP           = Sys::getUserIp();
        $this->BROWSER      = Sys::getUserBrowser();
        $this->LANGUAGE     = Sys::getUserLanguage();
        $this->SERVER       = Data::toObject($_SERVER);
        $this->COOKIE       = Data::toObject($_COOKIE);
        $this->SESSION      = (isset($_SESSION)) ? Data::toObject($_SESSION) : '';
        $this->FILES        = Data::toObject($_FILES);

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
            'IP'           => $this->IP,
            'BROWSER'      => $this->BROWSER,
            'LANGUAGE'     => $this->LANGUAGE,
            'HEADERS'      => $this->HEADERS,
            'GET'          => $this->GET,
            'POST'         => $this->POST,
            'SERVER'       => $this->SERVER,
            'COOKIE'       => $this->COOKIE,
            'SESSION'      => $this->SESSION,
            'FILES'        => $this->FILES,
        ];

        return $res;
    }

    public function tryHeader($name)
    {

        return Arr::getArrayValue($name, (array)$this->HEADERS);
    }
}
