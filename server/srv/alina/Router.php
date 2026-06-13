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
    }
    #endregion Instantiation
    ##################################################
    public function processUrl()
    {
        $this->initialUrl        = $_SERVER['REQUEST_URI'];
        $this->initialUrlDecoded = urldecode($_SERVER['REQUEST_URI']);
        $parsedUrl               = parse_url($this->initialUrlDecoded);

        if (isset($parsedUrl['query'])) {
            $this->strGetQuery = $parsedUrl['query'];
        }

        if (isset($parsedUrl['fragment'])) {
            $this->fragment = $parsedUrl['fragment'];
        }

        // Define path information
        if (isset(Request::obj()->GET->alinapath) and ! empty(Request::obj()->GET->alinapath)) {
            $this->pathAlias = trim(Request::obj()->GET->alinapath, '/');
            $this->pathSys   = (isset($this->vocAliasUrl) && ! empty($this->vocAliasUrl))
                ? \alina\Utils\Url::routeAccordance($this->pathAlias, $this->vocAliasUrl, true)
                : $this->pathAlias;
            $_pathParts     = explode('/', $this->pathSys);
            $this->pathPart = $_pathParts;

            if (isset($_pathParts[0]) && ! empty($_pathParts[0]) && ! is_numeric($_pathParts[0])) {
                $this->controller = array_shift($_pathParts);
            }

            if (isset($_pathParts[0]) && ! empty($_pathParts[0])) {
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

    public static function path($order = null)
    {
        $path = static::obj()->pathPart;

        if (isset($order)) {
            if (isset($path[$order])) {
                return $path[$order];
            }
        }

        return false;
    }
}
