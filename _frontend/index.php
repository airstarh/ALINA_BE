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

#region SHUTDOWN
register_shutdown_function(function () {
    error_log(\alina\utils\Sys::reportSpentTime([], ['FINAL']), 0);
});
#endregion SHUTDOWN

define('ALINA_ENV', 'DA');
switch (ALINA_ENV) {
    /**
     * 45A67BigComp2019
     */
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
ob_start();
ob_implicit_flush(FALSE);
$app    = \alina\app::set($config)->defineRoute()->mvcGo();
echo ob_get_clean();
