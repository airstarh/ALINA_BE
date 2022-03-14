<?php
define('ALINA_ENV', 'm45a');
##################################################
switch (ALINA_ENV) {
	case 'm45a':
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', '/srv/alina/_backend/alina');
        define('ALINA_PATH_TO_FRAMEWORK_CONFIG', '/srv/alina/_backend/_CFG/alina/default.php');
        define('ALINA_PATH_TO_APP', '/srv/alina/_backend/_aplications/m45a');
        define('ALINA_PATH_TO_APP_CONFIG', '/srv/alina/_backend/_CFG/apps/m45a/default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
}
##################################################