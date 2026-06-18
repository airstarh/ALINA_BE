<?php

namespace alina;

use alina\traits\Singleton;
use alina\Utils\Request as Request;

class Router
{
    ##################################################
    #region Instantiation

    use Singleton;

    public $initialUrl        = null;
    public $initialUrlDecoded = null;
    public $pathAlias         = null;
    public $pathSys           = null;
    public $forcedAlias       = null;
    public $controller        = null;
    public $action            = null;
    public $pathPart          = null;
    public $pathParameter     = [];
    public $vocAliasUrl       = [];
    public $strGetQuery       = '';
    public $fragment          = '';

    protected function __construct()
    {
        $vocAliasUrl       = AlinaCfg(['vocAliasUrl']);
        $bdVoc             = (new \alina\mvc\Model\router_alias())->getAsVoc();
        $this->vocAliasUrl = array_merge($vocAliasUrl, $bdVoc);
        $this->processUrl();
        $this->redirectIfNeeded();
    }

    #endregion Instantiation
    ##################################################

    private function processUrl()
    {
        $this->initialUrl        = $_SERVER['REQUEST_URI'];
        $this->initialUrlDecoded = Request::obj()->URL_PATH;
        $parsedUrl               = parse_url($this->initialUrlDecoded);

        if (isset($parsedUrl['query'])) {
            $this->strGetQuery = $parsedUrl['query'];
        }

        if (isset($parsedUrl['fragment'])) {
            $this->fragment = $parsedUrl['fragment'];
        }

        // Define path information
        if (! empty(Request::obj()->GET->alinapath)) {
            $this->pathAlias = trim(Request::obj()->GET->alinapath, '/');
            $this->pathSys   = (! empty($this->vocAliasUrl))
                    ? \alina\Utils\Url::routeAccordance($this->pathAlias, $this->vocAliasUrl, true)
                    : $this->pathAlias;
            $_pathParts     = explode('/', $this->pathSys);
            $this->pathPart = $_pathParts;

            if (! empty($_pathParts[0]) && ! is_numeric($_pathParts[0])) {
                $this->controller = array_shift($_pathParts);
                
                if (! \in_array($this->controller, \alina\Utils\Sys::getWhiteListController())) {
                    Alina()->mvcPageNotFound();;
                }
            }

            if (! empty($_pathParts[0])) {
                $this->action = array_shift($_pathParts);
            }
            else {
                $this->action = false;
            }
            $this->pathParameter = $_pathParts;
        }
        else {
            $this->controller = AlinaCfg('mvc/defaultController');
            $this->action     = AlinaCfg('mvc/defaultAction');
        }
    }

    private function redirectIfNeeded()
    {
        /*
         * This will redirect user to Page's Alias
         */
        if (AlinaCfg('forceSysPathToAlias')) {
            if ($this->pathAlias == $this->pathSys) {
                $this->forcedAlias = \alina\Utils\Url::routeAccordance($this->pathSys, $this->vocAliasUrl, false);

                if ($this->forcedAlias != $this->pathSys) {
                    $uri = [
                        'path'  => $this->forcedAlias,
                        'query' => $this->strGetQuery,
                    ];
                    $uri = \alina\Utils\Url::un_parse_url($uri);
                    \alina\Utils\Sys::redirect($uri);
                }
            }
        }
    }
}
