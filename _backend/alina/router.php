<?php
namespace alina;

class router
{
    public $initialUrl        = null;
    public $initialUrlDecoded = null;
    public $pathAlias         = null;
    public $pathSys           = null;
    public $forcedAlias       = null;
    public $controller        = null;
    public $action            = null;
    public $pathPart          = null;
    public $pathParameter     = [];
    public $vocAliasUrl       = null;

    /**
     * @param \alina\app $app
     */
    protected function __construct()
    {
    }

    public function processUrl()
    {
        $this->initialUrl        = $_SERVER['REQUEST_URI'];
        $this->initialUrlDecoded = urldecode($_SERVER['REQUEST_URI']);

        // Define path information
        if (isset($_GET['path']) AND !empty($_GET['path'])) {

            $this->pathAlias = $_GET['path'];
            $this->pathSys   = (isset($this->vocAliasUrl) && !empty($this->vocAliasUrl))
                ? routeAccordance($_GET['path'], $this->vocAliasUrl)
                : $_GET['path'];

            $_pathParts     = explode('/', $this->pathSys);
            $this->pathPart = $_pathParts;

            if (isset($_pathParts[0]) && !empty($_pathParts[0]) && !is_numeric($_pathParts[0])) {
                $this->controller = array_shift($_pathParts);
            }
            if (isset($_pathParts[0]) && !empty($_pathParts[0])) {
                $this->action = array_shift($_pathParts);
            }
            else {
                $this->action = FALSE;
            }
            $this->pathParameter = $_pathParts;
        }
        else {
            $this->controller = FALSE;
            $this->action     = FALSE;
        }
    }

    #region Instantiation
    static public $instance = null;

    /**
     * @return static
     */
    static public function obj()
    {
        if (!isset(static::$instance) || !is_a(static::$instance, '\alina\router')) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    #endregion Instantiation

    static public function path($order = null)
    {
        $path = static::obj()->pathPart;
        if (isset($order)) {
            if (isset($path[$order])) {
                return $path[$order];
            }
            else {
                return FALSE;
            }
        }
        else {
            return FALSE;
        }
    }
}