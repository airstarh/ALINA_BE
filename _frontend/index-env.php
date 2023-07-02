<?php
##################################################
#region HOST SPECIFIC
const ALINA_WEB_PATH = __DIR__;
const ALINA_ENV      = 'zero';
const ALINA_MODE     = 'DEV';
switch (ALINA_ENV) {
    case 'zero':
        define("ALINA_BACKEND", 'C:/_A001/REPOS/OWN/ALINA/_backend');
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
}
#endregion HOST SPECIFIC
##################################################
#region AUTOMATIC
const ALINA_PATH_TO_FRAMEWORK        = ALINA_BACKEND . '/alina';
const ALINA_PATH_TO_FRAMEWORK_CONFIG = ALINA_PATH_TO_FRAMEWORK . '/cfg/default.php';
const ALINA_PATH_TO_APP_CONFIG       = ALINA_PATH_TO_APP . '/cfg/default.php';
#endregion AUTOMATIC
##################################################
