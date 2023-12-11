<?php
define('ALINA_MICROTIME', $_SERVER['REQUEST_TIME_FLOAT'] ?: microtime(TRUE));
define('ALINA_TIME', $_SERVER['REQUEST_TIME'] ?: time());
define('ALINA_COOKIE_PAST', ALINA_TIME - 60 * 60);
define('ALINA_MAX_TIME_DIFF_SEC', 48 * 60 * 60);
define('ALINA_MIN_TIME_DIFF_SEC', 30);
define('ALINA_AUTH_EXPIRES', ALINA_TIME + ALINA_MAX_TIME_DIFF_SEC);
##################################################
# Make sure we see all available errors
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL | E_STRICT);
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
//error_reporting(E_ALL);
##################################################
#region SHUTDOWN
#### register_shutdown_function(function () {
####     error_log(\alina\utils\Sys::reportSpentTime(['FINAL'], []), 0);
#### });
#endregion SHUTDOWN
##################################################
require_once './index-env.php';
require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'App.php';
$config = require(ALINA_PATH_TO_APP_CONFIG);
//ob_start();
//ob_implicit_flush(FALSE);
$app = \alina\App::set($config)->defineRoute()->mvcGo();
//echo ob_get_clean();
