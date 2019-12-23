<?php

namespace alina;

use alina\mvc\model\watch_browser;
use alina\mvc\model\watch_ip;
use alina\mvc\model\watch_url_path;
use alina\mvc\model\watch_visit;
use alina\traits\Singleton;
use alina\utils\Request;

class Device
{
    #region Singleton
    use Singleton;

    protected $ip;
    protected $browser;
    protected $browserId;

    protected function __construct()
    {
        $this->ip      = Request::obj()->IP;
        $this->browser = Request::obj()->BROWSER;
    }

    protected function getBrowserId() {

    }
    #endregion Singleton
    ##################################################
    #region Watch
    #endregion Watch
    ##################################################
}
