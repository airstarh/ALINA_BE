<?php
define('ALINA_ENV', 'SAYSIMSIM_HOME');
##################################################
switch (ALINA_ENV) {
    case 'SAYSIMSIM_HOME':
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', 'C:\_A001\REPOS\OWN\ALINA\_backend\alina');
        define('ALINA_PATH_TO_FRAMEWORK_CONFIG', 'C:\_A001\REPOS\OWN\ALINA\_backend\_CFG\alina\default.php');
        define('ALINA_PATH_TO_APP', 'C:\_A001\REPOS\OWN\ALINA\_backend\_aplications\zero');
        define('ALINA_PATH_TO_APP_CONFIG', 'C:\_A001\REPOS\OWN\ALINA\_backend\_CFG\apps\zero\default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
	case 'SAYSIMSIM_SERVER':
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', '/srv/alina/_backend/alina');
        define('ALINA_PATH_TO_FRAMEWORK_CONFIG', '/srv/alina/_backend/_CFG/alina/default.php');
        define('ALINA_PATH_TO_APP', '/srv/alina/_backend/_aplications/zero');
        define('ALINA_PATH_TO_APP_CONFIG', '/srv/alina/_backend/_CFG/apps/zero/default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
}
##################################################