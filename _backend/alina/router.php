<?php

namespace alina;

use alina\traits\Singleton;

class router
{
    public $initialUrl        = NULL;
    public $initialUrlDecoded = NULL;
    public $pathAlias         = NULL;
    public $pathSys           = NULL;
    public $forcedAlias       = NULL;
    public $controller        = NULL;
    public $action            = NULL;
    public $pathPart          = NULL;
    public $pathParameter     = [];
    public $vocAliasUrl       = [];
    public $strGetQuery       = '';
    public $fragment          = '';

    ##################################################
    #region Instantiation
    use Singleton;

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
        if (isset($_GET['alinapath']) AND !empty($_GET['alinapath'])) {

            $this->pathAlias = trim($_GET['alinapath'], '/');
            $this->pathSys   = (isset($this->vocAliasUrl) && !empty($this->vocAliasUrl))
                ? \alina\utils\Url::routeAccordance($this->pathAlias, $this->vocAliasUrl, TRUE)
                : $this->pathAlias;

            $_pathParts     = explode('/', $this->pathSys);
            $this->pathPart = $_pathParts;

            if (isset($_pathParts[0]) && !empty($_pathParts[0]) && !is_numeric($_pathParts[0])) {
                $this->controller = array_shift($_pathParts);
            }
            if (isset($_pathParts[0]) && !empty($_pathParts[0])) {
                $this->action = array_shift($_pathParts);
            } else {
                $this->action = FALSE;
            }
            $this->pathParameter = $_pathParts;
        } else {
            $this->controller = AlinaCfg('mvc/defaultController');
            $this->action     = AlinaCfg('mvc/defaultAction');
        }
    }

    static public function path($order = NULL)
    {
        $path = static::obj()->pathPart;
        if (isset($order)) {
            if (isset($path[$order])) {
                return $path[$order];
            }
        }

        return FALSE;
    }
}
