<?php

// M45A
define('ALINA_MICROTIME', $_SERVER['REQUEST_TIME_FLOAT'] ?: microtime(true));
define('ALINA_TIME', $_SERVER['REQUEST_TIME'] ?: time());
define('ALINA_COOKIE_PAST', ALINA_TIME - 60 * 60);
define('ALINA_MAX_TIME_DIFF_SEC', 48 * 60 * 60);
define('ALINA_MIN_TIME_DIFF_SEC', 30);
define('ALINA_AUTH_EXPIRES', ALINA_TIME + ALINA_MAX_TIME_DIFF_SEC);
##################################################
require_once './index-env.php';
require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'App.php';
$config = require(ALINA_PATH_TO_APP_CONFIG);
//ob_start();
//ob_implicit_flush(FALSE);
$app = \alina\App::set($config)->defineRoute()->mvcGo();
//echo ob_get_clean();
