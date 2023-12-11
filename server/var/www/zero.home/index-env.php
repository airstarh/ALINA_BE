<?php
##################################################
#region HOST SPECIFIC
const ALINA_WEB_PATH = __DIR__;
const ALINA_ENV      = 'zero.home';
const ALINA_MODE     = 'DEV';
switch (ALINA_ENV) {
    case 'zero.home':
        define("ALINA_BACKEND", '/srv/php/_backend');
        define('ALINA_PATH_TO_APP', ALINA_BACKEND . '/alina_consumers/zero');
        break;
    case 'vov':
        define("ALINA_BACKEND", '/srv/alina/_backend');
        define('ALINA_PATH_TO_APP', ALINA_BACKEND . '/alina_consumers/vov');
        break;
    case 'm45a':
        define("ALINA_BACKEND", '/srv/alina/_backend');
        define('ALINA_PATH_TO_APP', ALINA_BACKEND . '/alina_consumers/m45a');
        break;
    case 'sss':
        define("ALINA_BACKEND", '/srv/alina/_backend');
        define('ALINA_PATH_TO_APP', ALINA_BACKEND . '/alina_consumers/sss');
        break;
    case 'osspb':
        define("ALINA_BACKEND", '/srv/php/_backend');
        define('ALINA_PATH_TO_APP', ALINA_BACKEND . '/alina_consumers/osspb');
        break;
}
#endregion HOST SPECIFIC
##################################################
#region AUTOMATIC
const ALINA_PATH_TO_FRAMEWORK        = ALINA_BACKEND . '/alina';
const ALINA_PATH_TO_FRAMEWORK_CONFIG = ALINA_PATH_TO_FRAMEWORK . '/cfg/default.php';
const ALINA_PATH_TO_APP_CONFIG       = ALINA_PATH_TO_APP . '/cfg/default.php';
#endregion AUTOMATIC
##################################################
