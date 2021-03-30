<?php
define('ALINA_ENV', 'DA');
##################################################
switch (ALINA_ENV) {
    case 'DA':
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', 'C:\_A001\REPOS\OWN\ALINA\_backend\alina');
        define('ALINA_PATH_TO_FRAMEWORK_CONFIG', 'C:\_A001\REPOS\OWN\ALINA\_backend\_CFG\alina\default.php');
        define('ALINA_PATH_TO_APP', 'C:\_A001\REPOS\OWN\ALINA\_backend\_aplications\zero');
        define('ALINA_PATH_TO_APP_CONFIG', 'C:\_A001\REPOS\OWN\ALINA\_backend\_CFG\apps\zero\default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
    case 'PROD':
        define('ALINA_MODE', 'PROD1');
        define('ALINA_PATH_TO_FRAMEWORK', '/srv/backend/alina');
        define('ALINA_PATH_TO_FRAMEWORK_CONFIG', '/srv/backend/_CFG/alina/default.php');
        define('ALINA_PATH_TO_APP', '/srv/backend/_aplications/zero');
        define('ALINA_PATH_TO_APP_CONFIG', '/srv/backend/_CFG/apps/zero/default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
}
##################################################