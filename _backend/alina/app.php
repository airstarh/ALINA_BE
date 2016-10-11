<?php
namespace alina;

class app
{
    #region Officials
    public $name    = 'Alina';
    public $version = 2;
    public $license = 'Free For All';
    #endregion Officials

    #region Initiation
    protected function __construct($config = [])
    {
        $this->setConfig($config);
    }

    public $config        = [];
    public $configDefault = [];

    protected function setConfig($config = [])
    {
        $defaultConfigPath   = normalPath(__DIR__ . '/configs/default.php');
        $defaultConfig       = require($defaultConfigPath);
        $this->configDefault = $defaultConfig;
        $this->config        = arrayMergeRecursive($this->configDefault, $config);
        static::$instance    = $this;
        return $this;
    }
    #endregion Initiation

    #region Instantiation
    static public $instance = null;

    /**
     * @return \alina\app
     */
    static public function get()
    {
        if (!isset(static::$instance) || !is_a(static::$instance, '\alina\app')) {
            throw new \Exception("App is not set");
        }
        return static::$instance;
    }

    /**
     * @return \alina\app
     */
    static public function set($config)
    {
        if (isset(static::$instance) && is_a('\alina\app', static::$instance)) {
            throw new \Exception("App is set already.");
        }
        $_this = new static($config);
        return $_this;
    }
    #endregion Instantiation

    #region Config manipulations
    static public function getConfig($path)
    {
        $_this = static::get();
        $cfg   = $_this->config;
        return getArrayValue($path, $cfg);
    }

    static public function getConfigDefault($path)
    {
        $_this = static::get();
        $cfg   = $_this->configDefault;
        return getArrayValue($path, $cfg);
    }

    #endregion Config manipulations

    #region Routes
    /** @var \alina\core\router */
    public $router;

    public function defineRoute()
    {
        $this->router              = \alina\core\router::obj();
        $this->router->vocAliasUrl = static::getConfig(['vocAliasUrl']);
        $this->router->processUrl();

        /*
         * This will redirect user to Page's Alias
         */
        if (static::getConfig(['forceSysPathToAlias'])) {
            if ($this->router->pathAlias == $this->router->pathSys) {
                $this->router->forcedAlias = routeAccordance($this->router->pathSys, $this->router->vocAliasUrl, FALSE);
                if ($this->router->forcedAlias != $this->router->pathSys) {
                    redirect($this->router->forcedAlias);
                }
            }
        }
        return $this;
    }
    #endregion Routes

    #region MVC
    public $controller;
    public $action;
    public $actionParams = [];
    const ACTION_PREFIX  = 'action';
    const DEFAULT_ACTION = 'index';

    public function mvcControllerAction($controller, $action, $params = [])
    {
        return returnClassMethod($controller, $action, $params);
    }

    public function fullActionName($name)
    {
        return static::ACTION_PREFIX . ucfirst($name);
    }

    public function mvcGo()
    {
        $this->controller   = $this->router->controller;
        $this->action       = $this->router->action;
        $this->actionParams = $this->router->pathParameter;

        if (empty($this->controller) && empty($this->action)) {
            return $this->mvcDefaultPage();
        }
        if (empty($this->controller)) {
            return $this->mvcPageNotFound();
        }
        if (empty($this->action)) {
            $this->action = static::DEFAULT_ACTION;
        }

        // Defined by route in user app.
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = $this->controller;
            $controller     = fullClassName($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName($this->action);
            $params         = $this->actionParams;
            return $this->mvcControllerAction($controller, $action, $params);
        }
        catch (\Exception $e) {
            // Defined by route in Alina
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = $this->controller;
                $controller     = fullClassName($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName($this->action);;
                $params = $this->actionParams;
                return $this->mvcControllerAction($controller, $action, $params);

            }
            catch (\Exception $e) {
                return $this->mvcPageNotFound();
            }
        }
    }

    public function mvcPageNotFound()
    {
        http_response_code(404);
        // 404 of user app
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = static::getConfig('mvc/pageNotFoundController');
            $controller     = fullClassName($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/pageNotFoundAction'));
            return $this->mvcControllerAction($controller, $action);
        }
        catch (\Exception $e) {
            // 404 of Alina
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/pageNotFoundController');
                $controller     = fullClassName($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/pageNotFoundAction'));
                return $this->mvcControllerAction($controller, $action);
            }
            catch (\Exception $e) {
                throw new \Exception('Total Fail');
            }
        }

    }

    public function mvcDefaultPage()
    {
        // Default page of user app
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = static::getConfig('mvc/defaultController');
            $controller     = fullClassName($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/defaultAction'));
            return $this->mvcControllerAction($controller, $action);
        }
        catch (\Exception $e) {
            // Default page of Alina
            try {
                $namespace      = static::getConfigDefault(['appNamespace']);
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/defaultController');
                $controller     = fullClassName($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/defaultAction'));
                return $this->mvcControllerAction($controller, $action);
            }
            catch (\Exception $e) {
                throw new \Exception('No index page');
            }
        }

    }
    #endregion MVC
}