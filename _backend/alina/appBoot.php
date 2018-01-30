<?php

define('ALINA_MICROTIME', microtime(TRUE));
define('ALINA_TIME', time());
define('ALINA_COOKIE_PAST', ALINA_TIME - 60 * 60);

define('ALINA_ENV', 'HOME_2_UNIT_TEST');
switch (ALINA_ENV) {
    case 'HOME_2_UNIT_TEST':
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', 'F:\_REPO\ALINA\_backend\alina');
        define('ALINA_PATH_TO_APP', 'F:\_REPO\ALINA\_backend\alina');
        define('ALINA_PATH_TO_APP_CONFIG', 'F:\_REPO\ALINA\_backend\alina\configs\default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
}

require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'app.php';
$config = require(ALINA_PATH_TO_APP_CONFIG);
$app = \alina\app::set($config);