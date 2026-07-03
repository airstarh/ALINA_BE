<?php

##################################################
#region HOST SPECIFIC
const ALINA_WEB_PATH = __DIR__;
define('ALINA_MODE', getenv('ALINA_MODE'));
const ALINA_BACKEND     = '/srv';
const ALINA_PATH_TO_APP = ALINA_BACKEND . '/alina_consumers/vov';
#endregion HOST SPECIFIC
##################################################
#region AUTOMATIC
const ALINA_PATH_TO_FRAMEWORK        = ALINA_BACKEND . '/alina';
const ALINA_PATH_TO_FRAMEWORK_CONFIG = ALINA_PATH_TO_FRAMEWORK . '/cfg/default.php';
const ALINA_PATH_TO_APP_CONFIG       = ALINA_PATH_TO_APP . '/cfg/default.php';
#endregion AUTOMATIC
##################################################
