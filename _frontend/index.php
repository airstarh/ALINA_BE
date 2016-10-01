<?php
namespace frontend;

use base\application as core;

// Be sure we see all available errors
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(E_ALL | E_STRICT);

spl_autoload_extensions(".php");
spl_autoload_register();
// Fix of PHP bug. Please, see: https://bugs.php.net/bug.php?id=52339
//spl_autoload_register(function(){});

# regions Define Environment
//define('ALINA_ENV', 'STAGE');
define('ALINA_ENV', 'HOME');
//define('ALINA_ENV', 'DA');
# endregion Define Environment

switch (ALINA_ENV) {
    case 'DA':
        define('PATH_TO_CORE', 'D:\_processes\_outscope\001_GutHub_MyMVC_Alina\_rep001\_backend\core');
        define('PATH_TO_CONFIG', 'D:\_processes\_outscope\001_GutHub_MyMVC_Alina\_rep001\_backend\applications\liga\config-da.php');
        define('APP_FRONTEND_PATH', dirname(__FILE__));
        break;
    case 'HOME':
        define('PATH_TO_CORE', 'E:\_google_disc\_project\001-education\_rep001\_backend\core');
        define('PATH_TO_CONFIG', 'E:\_google_disc\_project\001-education\_rep001\_backend\applications\liga\config-home.php');
        define('APP_FRONTEND_PATH', dirname(__FILE__));
        break;
    default:
        define('PATH_TO_CORE', realpath(dirname(__FILE__).'/../cgi-bin/core'));
        define('PATH_TO_CONFIG', '../cgi-bin/applications/liga/config.php');
        define('APP_FRONTEND_PATH', dirname(__FILE__));
        break;
}

$config = require_once PATH_TO_CONFIG;
set_include_path(get_include_path() . PATH_SEPARATOR . PATH_TO_CORE);
set_include_path(get_include_path() . PATH_SEPARATOR . PATH_TO_CORE.'/vendor');

spl_autoload_register(function($name)
{
    $namespaceToPath = str_replace('\\', DIRECTORY_SEPARATOR, $name);
    $extension = '.php';
    $classPath = stream_resolve_include_path($namespaceToPath.$extension);
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

core::setApp($config)->url()->run();

//\helper\debug::show($app);

/*
\helper\debug::show($_SERVER);
\helper\debug::show(explode(';', get_include_path()));
\helper\debug::show(get_declared_classes());

\helper\debug::show('================================');
\helper\debug::show($app);
\helper\debug::show(explode(';', get_include_path()));
*/