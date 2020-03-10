<?php

namespace alina;

use alina\mvc\model\CurrentUser;
use alina\utils\Sys;
use alina\utils\Url;

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
        $this->init();
        $this->autoload($config);
        $this->setConfig($config);
        #####
        set_exception_handler([\alina\exceptionCatcher::obj(), 'exception']);
        set_error_handler([\alina\exceptionCatcher::obj(), 'error']);
        #####
        AlinaResponseSuccess(1);
        #####
        Sys::setCrossDomainHeaders();
        //        if (CurrentUser::obj()->isLoggedIn()) {
        //            Message::set('Logged In !!!' . ALINA_TIME);
        //        } else {
        //            Message::set('Not logged in (((' . ALINA_TIME);
        //        }
        //CurrentUser::obj()->messages();
    }

    protected function init()
    {
        // Facade functions
        require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . '_dependent' . DIRECTORY_SEPARATOR . '_autoloadFunctions.php';
    }

    protected function autoload($config)
    {
        spl_autoload_extensions(".php");
        spl_autoload_register();
        // Fix of PHP bug. Please, see: https://bugs.php.net/bug.php?id=52339
        //spl_autoload_register(function(){});
        spl_autoload_register(function ($class) use ($config) {
            $extension = '.php';

            // For Application
            if (!isset($config['appNamespace']) || empty($config['appNamespace'])) {
                return NULL;
            }
            $appNamespace = $config['appNamespace'];
            $className    = ltrim($class, '\\');
            $className    = ltrim($className, $appNamespace);
            $className    = ltrim($className, '\\');
            $className    = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            $classFile    = $className . $extension;
            $classPath    = ALINA_PATH_TO_APP . DIRECTORY_SEPARATOR . $classFile;
            if (file_exists($classPath)) {
                require_once $classPath;
            }

            // For Alina
            $appNamespace = 'alina';
            $className    = ltrim($class, '\\');
            $className    = ltrim($className, $appNamespace);
            $className    = ltrim($className, '\\');
            $className    = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            $classFile    = $className . $extension;
            $classPath    = ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . $classFile;
            if (file_exists($classPath)) {
                require_once $classPath;
            }

            // For Vendors
            //            $className = ltrim($class, '\\');
            //            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            //            $classFile = $className . $extension;
            //            $classPath = ALINA_PATH_TO_FRAMEWORK .DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $classFile;
            //            if (file_exists($classPath)) {
            //                require_once $classPath;
            //            }
        });

        // ToDo: Resolve for User-defined Application!!!
        require_once __DIR__ . '/vendor/autoload.php';
    }

    protected $config        = [];
    protected $configDefault = [];

    protected function setConfig($config = [])
    {
        $defaultConfigPath   = \alina\utils\FS::normalizePath(ALINA_PATH_TO_FRAMEWORK . '/configs/default.php');
        $defaultConfig       = require($defaultConfigPath);
        $this->configDefault = $defaultConfig;
        $this->config        = \alina\utils\Arr::arrayMergeRecursive($this->configDefault, $config);
        static::$instance    = $this;

        return $this;
    }
    #endregion Initiation

    #region Instantiation
    static protected $instance = NULL;

    /**
     * @return static
     * @throws \Exception
     */
    static public function get()
    {
        if (!isset(static::$instance) || !is_a(static::$instance, get_class())) {
            throw new \Exception("Alina App is not set");
        }

        return static::$instance;
    }

    /**
     * @param array $config
     * @return app
     * @throws \Exception
     */
    static public function set($config)
    {
        if (isset(static::$instance) && is_a(static::$instance, get_class())) {
            throw new \Exception("Alina App is set already.");
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

        return \alina\utils\Arr::getArrayValue($path, $cfg);
    }

    static public function getConfigDefault($path)
    {
        $_this = static::get();
        $cfg   = $_this->configDefault;

        return \alina\utils\Arr::getArrayValue($path, $cfg);
    }

    #endregion Config manipulations

    #region Namespace Resolver
    public function resolveClassName($nsPath)
    {
        $ns       = static::getConfig('appNamespace');
        $fullPath = \alina\utils\Resolver::buildClassNameFromBlocks($ns, $nsPath);
        if (class_exists($fullPath)) {
            return $fullPath;
        }

        $ns       = static::getConfigDefault('appNamespace');
        $fullPath = \alina\utils\Resolver::buildClassNameFromBlocks($ns, $nsPath);
        if (class_exists($fullPath)) {
            return $fullPath;
        }

        if (class_exists($nsPath)) {
            return $nsPath;
        }

        throw new \ErrorException("Relative Class {$nsPath} is not defined.");
    }

    /**
     * Resolve Method Name in proper Case-Sensitive name.
     * @param object|string $classNameOrObject
     * @param string $methodName
     * @return bool
     */
    public function resolveMethodName($classNameOrObject, $methodName)
    {
        $methods = get_class_methods($classNameOrObject);
        foreach ($methods as $m) {
            if (strtolower($m) === strtolower($methodName)) {
                return $m;
            }
        }

        return FALSE;
    }
    #endregion Namespace Resolver

    #region Paths Resolver
    public function resolvePath($path)
    {
        // -Check if Path exists in User Application directory.
        $fullPath = \alina\utils\FS::buildPathFromBlocks(ALINA_PATH_TO_APP, $path);
        if (FALSE !== ($rp = realpath($fullPath))) {
            return $rp;
        }

        // -Check if Path exists in Alina directory.
        $fullPath = \alina\utils\FS::buildPathFromBlocks(ALINA_PATH_TO_FRAMEWORK, $path);
        if (FALSE !== ($rp = realpath($fullPath))) {
            return $rp;
        }

        // -Check if Path exists as is.
        if (FALSE !== ($rp = realpath($path))) {
            return $rp;
        }

        throw new \ErrorException("Path {$path} is not defined.");
    }
    #endregion Paths Resolver

    #region Routes
    /** @var \alina\router */
    public $router;

    public function defineRoute()
    {
        $this->router              = \alina\router::obj();
        $this->router->vocAliasUrl = static::getConfig(['vocAliasUrl']);
        $this->router->processUrl();

        ##################################################
        #region Redirect
        /*
         * This will redirect user to Page's Alias
         */
        if (AlinaCFG('forceSysPathToAlias')) {
            if ($this->router->pathAlias == $this->router->pathSys) {
                $this->router->forcedAlias = \alina\utils\Url::routeAccordance($this->router->pathSys, $this->router->vocAliasUrl, FALSE);
                if ($this->router->forcedAlias != $this->router->pathSys) {
                    $uri = [
                        'path'  => $this->router->forcedAlias,
                        'query' => $this->router->strGetQuery,
                    ];
                    $uri = Url::un_parse_url($uri);
                    \alina\utils\Sys::redirect($uri);
                }
            }
        }
        #endregion Redirect
        ##################################################
        Watcher::obj()->logVisitsToDb();

        return $this;
    }
    #endregion Routes

    #region MVC
    public $controller;
    public $action;
    public $actionParams        = [];
    public $currentController   = '';
    public $currentAction       = '';
    public $currentActionParams = [];
    const ACTION_PREFIX = 'action';

    public function mvcControllerAction($controllerName, $action, $params = [])
    {
        if (!class_exists($controllerName, TRUE)) {
            throw new \alina\exception("No Class: $controllerName");
        }

        $go = new $controllerName();

        if (FALSE === ($action = $this->resolveMethodName($go, $action))) {
            throw new \alina\exception("No Method: $action");
        }

        if (!is_array($params)) {
            $params = [$params];
        }

        $this->currentController   = get_class($go);
        $this->currentAction       = $action;
        $this->currentActionParams = $params;

        return call_user_func_array([$go, $action], $params);
    }

    public function fullActionName($name)
    {
        return static::ACTION_PREFIX . ucfirst($name);
    }

    public function mvcGo($controller = NULL, $action = NULL, $params = NULL)
    {
        $this->controller   = (isset($controller)) ? $controller : $this->router->controller;
        $this->action       = (isset($action)) ? $action : $this->router->action;
        $this->actionParams = (isset($params)) ? $params : $this->router->pathParameter;

        if (empty($this->controller) && empty($this->action)) {
            return $this->mvcDefaultPage();
        }
        if (empty($this->controller)) {
            return $this->mvcPageNotFound();
        }
        if (empty($this->action)) {
            $this->action = static::getConfigDefault('mvc/defaultAction');
        }

        // Defined by route in user app.
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = $this->controller;
            $controller     = \alina\utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName($this->action);
            $params         = $this->actionParams;

            return $this->mvcControllerAction($controller, $action, $params);
        } catch (\alina\exception $e) {
            // Defined by route in Alina
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = $this->controller;
                $controller     = \alina\utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName($this->action);
                $params         = $this->actionParams;

                return $this->mvcControllerAction($controller, $action, $params);
            } catch (\alina\exception $e) {
                return $this->mvcPageNotFound();
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
            $controller     = \alina\utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/defaultAction'));

            return $this->mvcControllerAction($controller, $action);
        } catch (\alina\exception $e) {
            // Default page of Alina
            try {
                $namespace      = static::getConfigDefault(['appNamespace']);
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/defaultController');
                $controller     = \alina\utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/defaultAction'));

                return $this->mvcControllerAction($controller, $action);
            } catch (\alina\exception $e) {
                throw new \alina\exception('No index page');
            }
        }
    }

    public function mvcPageNotFound()
    {
        // ToDo: line below does not work with Nginx correct. Investigate.
        //http_response_code(404);

        // 404 of user app
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = static::getConfig('mvc/pageNotFoundController');
            $controller     = \alina\utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/pageNotFoundAction'));

            return $this->mvcControllerAction($controller, $action);
        } catch (\alina\exception $e) {
            // 404 of Alina
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/pageNotFoundController');
                $controller     = \alina\utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/pageNotFoundAction'));

                return $this->mvcControllerAction($controller, $action);
            } catch (\alina\exception $e) {
                throw new \Exception('Alina Total Fail');
            }
        }
    }
    #endregion MVC
}
