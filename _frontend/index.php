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

define('ALINA_ENV', 'HOME');
switch (ALINA_ENV) {
    case 'HOME':
        define('ALINA_MODE', 'dev');
        define('PATH_TO_ALINA_BACKEND_DIR', 'C:\_REPO\ALINA\_backend\alina');
        define('PATH_TO_APP_DIR', 'C:\_REPO\ALINA\_backend\_aplications\zero');
        define('PATH_TO_APP_CONFIG_FILE', 'C:\_REPO\ALINA\_backend\_aplications\zero\configs\default.php"');
        define('PATH_TO_FRONT_END_ROOT', __DIR__);
        break;
}

require_once PATH_TO_ALINA_BACKEND_DIR.DIRECTORY_SEPARATOR.'app.php';
$config = require(PATH_TO_APP_CONFIG_FILE);
$app = \alina\app::set($config)->defineRoute()->mvcGo();

// ToDo: Delete on prod
if (ALINA_MODE !== 'PROD') {
    $alinaTimeSpent = microtime(TRUE) - ALINA_MICROTIME;
    print_r("<h2>Time spent: $alinaTimeSpent</h2>");
}
