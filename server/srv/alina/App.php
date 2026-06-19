<?php

namespace alina;

use alina\mvc\Model\CurrentUser;
use alina\Utils\Arr;
use alina\Utils\Request;
use alina\Utils\Sys;

final class App
{
    ####################################################################################################
    #region Officials
    public $name    = 'Alina';
    public $version = 2;
    public $license = 'Free For All';
    #endregion Officials
    ####################################################################################################
    #region MVC
    public $controller;
    public $action;
    public $actionParams        = [];
    public $currentController   = '';
    public $currentAction       = '';
    public $currentActionParams = [];
    public const ACTION_PREFIX  = 'action';
    #region MVC
    ####################################################################################################
    #region Initiation

    private $config        = [];
    private $configDefault = [];

    private function __construct($config = [])
    {
        $this->autoload($config);
        $this->setConfig($config);
        #####
        set_exception_handler([AppExceptionCatcher::obj(), 'exception']);
        set_error_handler([AppExceptionCatcher::obj(), 'error']);
        #####
        Request::obj();
        CurrentUser::obj();
        #####
        AlinaResponseSuccess(1);
        #####
        Sys::setCrossDomainHeaders();
        #####
        Watcher::obj()->logVisitsToDb();
        #####
        Message::fromRequest();
        MessageAdmin::fromRequest();
        #####
    }

    private function autoload($config)
    {
        require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . '_dependent' . DIRECTORY_SEPARATOR . '_autoloadFunctions.php';
        require_once __DIR__ . '/vendor/autoload.php';
        spl_autoload_extensions(".php");
        spl_autoload_register();
        // Fix of PHP bug. Please, see: https://bugs.php.net/bug.php?id=52339
        //spl_autoload_register(function(){});
        spl_autoload_register(function ($class) use ($config) {
            $extension = '.php';

            // For Application
            if (isset($config['appNamespace'])) {
                $appNamespace = $config['appNamespace'];
                $className    = ltrim($class, '\\');
                $className    = ltrim($className, $appNamespace);
                $className    = ltrim($className, '\\');
                $className    = str_replace('\\', DIRECTORY_SEPARATOR, $className);
                $classFile    = $className . $extension;
                $classPath    = ALINA_PATH_TO_APP . DIRECTORY_SEPARATOR . $classFile;

                if (false !== ($res = Alina_file_exists($classPath))) {
                    require_once $res;

                    return null;
                }
            }
            // For Alina
            $appNamespace = 'alina';
            $className    = ltrim($class, '\\');
            $className    = ltrim($className, $appNamespace);
            $className    = ltrim($className, '\\');
            $className    = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            $classFile    = $className . $extension;
            $classPath    = ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . $classFile;

            if (false !== ($res = Alina_file_exists($classPath))) {
                require_once $res;

                return null;
            }

            return null;
        });

        return null;
    }



    private function setConfig(array $config = [])
    {
        $defaultConfigPath   = Utils\FS::normalizePath(ALINA_PATH_TO_FRAMEWORK_CONFIG);
        $defaultConfig       = require($defaultConfigPath);
        $this->configDefault = $defaultConfig;
        $this->config        = Arr::arrayMergeRecursive($this->configDefault, $config);
        static::$instance    = $this;

        return $this;
    }
    #endregion Initiation
    ####################################################################################################
    #region Instantiation

    /** @var static $instance */
    protected static $instance = null;

    /**
     * @return static
     * @throws \Exception
     */
    public static function get()
    {
        if (! isset(static::$instance) || ! is_a(static::$instance, get_class())) {
            throw new \Exception("Alina App is not set");
        }

        return static::$instance;
    }

    /**
     * @param array $config
     * @return App
     * @throws \Exception
     */
    public static function set($config)
    {
        if (isset(static::$instance) && is_a(static::$instance, get_class())) {
            return static::$instance;
        }
        $_this = new static($config);

        return $_this;
    }
    #endregion Instantiation
    ####################################################################################################
    #region Config manipulations
    public static function getConfig($path)
    {
        $_this = static::get();
        $cfg   = $_this->config;

        return Arr::getArrayValue($path, $cfg);
    }

    public static function getConfigDefault($path)
    {
        $_this = static::get();
        $cfg   = $_this->configDefault;

        return Arr::getArrayValue($path, $cfg);
    }

    #endregion Config manipulations
    ####################################################################################################
    #region Namespace Resolver
    /**
     * Resolve Method Name in proper Case-Sensitive name.
     * @param object|string $classNameOrObject
     * @param string $methodName
     * @return bool | string
     */
    public function resolveMethodName($classNameOrObject, $methodName)
    {
        $methods = get_class_methods($classNameOrObject);

        foreach ($methods as $m) {
            if (strtolower($m) === strtolower($methodName)) {
                return $m;
            }
        }

        return false;
    }
    #endregion Namespace Resolver
    ####################################################################################################
    #region Paths Resolver
    public function resolvePath($path)
    {
        // -Check if Path exists in User Application directory.
        $fullPath = Utils\FS::buildPathFromBlocks(ALINA_PATH_TO_APP, $path);

        if (false !== ($rp = realpath($fullPath))) {
            return $rp;
        }

        #####
        if (false !== ($rp = realpath(DIRECTORY_SEPARATOR . $fullPath))) {
            return $rp;
        }
        #####
        #####
        // -Check if Path exists in Alina directory.
        $fullPath = Utils\FS::buildPathFromBlocks(ALINA_PATH_TO_FRAMEWORK, $path);

        if (false !== ($rp = realpath($fullPath))) {
            return $rp;
        }

        if (false !== ($rp = realpath(DIRECTORY_SEPARATOR . $fullPath))) {
            return $rp;
        }

        #####
        #####
        // -Check if Path exists as is.
        if (false !== ($rp = realpath($path))) {
            return $rp;
        }

        throw new \ErrorException("Path {$path} is not defined.");
    }
    #endregion Paths Resolver
    ####################################################################################################
    #region Routes

    public Router $router;

    public function defineRoute()
    {
        $this->router = Router::obj();

        return $this;
    }
    #endregion Routes
    ####################################################################################################
    #region MVC

    private function mvcControllerAction($controllerName, $action, $params = [])
    {
        if (! class_exists($controllerName, true)) {
            throw new AppException("No Class: $controllerName");
        }
        $go = new $controllerName();

        if (false === ($action = $this->resolveMethodName($go, $action))) {
            throw new AppException("No Method: $action");
        }

        if (! is_array($params)) {
            $params = [$params];
        }
        $this->currentController   = get_class($go);
        $this->currentAction       = $action;
        $this->currentActionParams = $params;

        return call_user_func_array([$go, $action], $params);
    }

    private function fullActionName($name)
    {
        return static::ACTION_PREFIX . ucfirst($name);
    }

    public function mvcGo($controller = null, $action = null, $params = null)
    {
        // Set controller, action, and parameters from input or fallback to router values
        $this->controller   = $controller ?? $this->router->controller;
        $this->action       = $action     ?? $this->router->action;
        $this->actionParams = $params     ?? $this->router->pathParameter;

        // If both controller and action are missing, show default page
        if (empty($this->controller) && empty($this->action)) {
            return $this->mvcDefaultPage();
        }

        // If controller is missing, show 404
        if (empty($this->controller)) {
            return $this->mvcPageNotFound();
        }

        // If action is missing, use default action from config
        if (empty($this->action)) {
            $this->action = static::getConfigDefault('mvc/defaultAction');
        }

        // First attempt: Use application-defined configuration
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = Utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $this->controller);
            $action         = $this->fullActionName($this->action);
            $params         = $this->actionParams;

            return $this->mvcControllerAction($controller, $action, $params);
        }
        catch (AppException $e) {
            // Fallback: Use Alina core configuration if app config fails
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = Utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $this->controller);
                $action         = $this->fullActionName($this->action);
                $params         = $this->actionParams;

                return $this->mvcControllerAction($controller, $action, $params);
            }
            catch (AppException $e) {
                // If both attempts fail, return 404
                return $this->mvcPageNotFound();
            }
        }
    }


    private function mvcDefaultPage()
    {
        // Default page of user app
        try {
            $namespace      = static::getConfig('appNamespace');
            $controllerPath = static::getConfig('mvc/structure/controller');
            $controller     = static::getConfig('mvc/defaultController');
            $controller     = Utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/defaultAction'));

            return $this->mvcControllerAction($controller, $action);
        }
        catch (AppException $e) {
            // Default page of Alina
            try {
                $namespace      = static::getConfigDefault(['appNamespace']);
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/defaultController');
                $controller     = Utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/defaultAction'));

                return $this->mvcControllerAction($controller, $action);
            }
            catch (AppException $e) {
                throw new AppException('No index page');
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
            $controller     = Utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
            $action         = $this->fullActionName(static::getConfig('mvc/pageNotFoundAction'));

            return $this->mvcControllerAction($controller, $action);
        }
        catch (AppException $e) {
            // 404 of Alina
            try {
                $namespace      = static::getConfigDefault('appNamespace');
                $controllerPath = static::getConfigDefault('mvc/structure/controller');
                $controller     = static::getConfigDefault('mvc/pageNotFoundController');
                $controller     = Utils\Resolver::buildClassNameFromBlocks($namespace, $controllerPath, $controller);
                $action         = $this->fullActionName(static::getConfigDefault('mvc/pageNotFoundAction'));

                return $this->mvcControllerAction($controller, $action);
            }
            catch (AppException $e) {
                throw new \Exception('Alina Total Fail');
            }
        }
    }
    #endregion MVC
    ####################################################################################################
}
