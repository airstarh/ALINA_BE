<?php
define('ALINA_ENV', 'sss');
##################################################
switch (ALINA_ENV) {
    case 'sss':
        define('ALINA_MODE', 'PROD1');
        define('ALINA_PATH_TO_FRAMEWORK', '/srv/alina/_backend/alina');
        define('ALINA_PATH_TO_FRAMEWORK_CONFIG', '/srv/alina/_backend/_CFG/alina/default.php');
        define('ALINA_PATH_TO_APP', '/srv/alina/_backend/_aplications/zero');
        define('ALINA_PATH_TO_APP_CONFIG', '/srv/alina/_backend/_CFG/apps/zero/default.php');
        define('ALINA_WEB_PATH', __DIR__);
        break;
}
##################################################
