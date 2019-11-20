<?php

namespace alina\utils;

use alina\mvc\model\tag_to_entity;

class Request
{
    protected static $inst = NULL;
    protected        $sReqMethod;
    protected        $sReqUrl;
    protected        $sUserIp;
    protected        $sUserBrowser;
    protected        $oReqGet;
    protected        $oReqPost;
    protected        $oReqHeaders;

    /**
     * @return static
     */
    static public function obj()
    {
        if (empty(static::$inst)) {
            static::$inst = new static();
        }

        return static::$inst;
    }

    protected function __construct()
    {
        $this->oReqGet      = Sys::resolveGetDataAsObject();
        $this->oReqPost     = Sys::resolvePostDataAsObject();
        $this->oReqHeaders  = getallheaders();
        $this->sUserBrowser = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $this->sUserIp      = Sys::getUserIp();
    }

    public function all()
    {
        return [
            'oReqHeaders'  => $this->oReqHeaders,
            'oReqGet'      => $this->oReqGet,
            'oReqPost'     => $this->oReqPost,
            'sUserBrowser' => $this->sUserBrowser,
            'sUserIp'      => $this->sUserIp,
        ];
    }
}
