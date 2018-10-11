<?php
define('ALINA_MICROTIME', microtime(TRUE));
define('ALINA_TIME', time());
define('ALINA_COOKIE_PAST', ALINA_TIME - 60 * 60);

// Make sure we see all available errors
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
//error_reporting(E_ALL);

define('ALINA_ENV', 'HOME_2');
switch (ALINA_ENV) {
    case 'HOME':
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', 'C:\_REPO\ALINA\_backend\alina');
        define('ALINA_PATH_TO_APP', 'C:\_REPO\ALINA\_backend\_aplications\zero');
        define('ALINA_PATH_TO_APP_CONFIG', 'C:\_REPO\ALINA\_backend\_aplications\zero\configs\default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
    case 'HOME_2':
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', 'H:\_REPO\ALINA\_backend\alina');
        define('ALINA_PATH_TO_APP', 'H:\_REPO\ALINA\_backend\_aplications\zero');
        define('ALINA_PATH_TO_APP_CONFIG', 'H:\_REPO\ALINA\_backend\_aplications\zero\configs\default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
	case 'DA':
		define('ALINA_MODE', 'dev');
		define('ALINA_PATH_TO_FRAMEWORK', 'D:\_processes\_outscope\012_alina\_backend\alina');
		define('ALINA_PATH_TO_APP', 'D:\_processes\_outscope\012_alina\_backend\_aplications\zero');
		define('ALINA_PATH_TO_APP_CONFIG', 'D:\_processes\_outscope\012_alina\_backend\_aplications\zero\configs\default.php');
		define('ALINA_WEB_PATH', __DIR__);
		break;
}

require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'app.php';
$config = require(ALINA_PATH_TO_APP_CONFIG);
$app = \alina\app::set($config)->defineRoute()->mvcGo();

// ToDo: Delete on prod
if (ALINA_MODE !== 'PROD') {
    $alinaTimeSpent = microtime(TRUE) - ALINA_MICROTIME;
    error_log("<<<<<<< Time Spent: {$alinaTimeSpent}",0);
}
