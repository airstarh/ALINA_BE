<?php
// Make sure we see all available errors
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(E_ALL | E_STRICT);
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
//error_reporting(E_ALL);

define('ALINA_ENV', 'HOME');
switch (ALINA_ENV) {
    case 'HOME':
        define('PATH_TO_ALINA_BACKEND_DIR', 'E:\___projects\alina\_backend\alina');
        define('PATH_TO_APP_DIR', 'E:\___projects\alina\_backend\_aplications\zero');
        define('PATH_TO_APP_CONFIG_FILE', 'E:\___projects\alina\_backend\_aplications\zero\configs\default.php');
        break;
}

// Fasade functions
require_once PATH_TO_ALINA_BACKEND_DIR . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . '_independent' . DIRECTORY_SEPARATOR . '_autoloadFunctions.php';
require_once PATH_TO_ALINA_BACKEND_DIR . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . '_dependent' . DIRECTORY_SEPARATOR . '_autoloadFunctions.php';

$config = require(PATH_TO_APP_CONFIG_FILE);

spl_autoload_extensions(".php");
spl_autoload_register();
// Fix of PHP bug. Please, see: https://bugs.php.net/bug.php?id=52339
//spl_autoload_register(function(){});
spl_autoload_register(function ($class) use ($config){
    $extension = '.php';

    // For Alina
    $className      = ltrim($class, '\\' );
    $className      = ltrim($className, 'alina');
    $className      = ltrim($className, '\\' );
    $className      = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $classFile = $className.$extension;
    $classPath = PATH_TO_ALINA_BACKEND_DIR.DIRECTORY_SEPARATOR.$classFile;
    if (file_exists($classPath)) {
        require_once $classPath;
    }

    // For Application
    if (!isset($config['appNamespace']) || empty($config['appNamespace'])) {
        return null;
    }
    $className      = ltrim($class, '\\' );
    $className      = ltrim($className, $config['appNamespace']);
    $className      = ltrim($className, '\\' );
    $className      = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $classFile = $className.$extension;
    $classPath = PATH_TO_APP_DIR.DIRECTORY_SEPARATOR.$classFile;
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

//$app = new \alina\app($config);
$app = \alina\app::set($config)->defineRoute()->mvcGo();

echo '<pre>';
print_r(\alina\app::get()->router);
echo '</pre>';