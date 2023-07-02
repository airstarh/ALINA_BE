<?php

/**
 * This File exists mostly for Unit Test needs.
 *This file DOES NOT participate in Application usage!!!
 */
class AlinaAdapter
{
    static public function initiate()
    {
        #####
        define('ALINA_MICROTIME', $_SERVER['REQUEST_TIME_FLOAT'] ?: microtime(TRUE));
        define('ALINA_TIME', $_SERVER['REQUEST_TIME'] ?: time());
        define('ALINA_COOKIE_PAST', ALINA_TIME - 60 * 60);
        define('ALINA_MAX_TIME_DIFF_SEC', 48 * 60 * 60);
        define('ALINA_MIN_TIME_DIFF_SEC', 30);
        define('ALINA_AUTH_EXPIRES', ALINA_TIME + ALINA_MAX_TIME_DIFF_SEC);
        #####
        define('ALINA_MODE', 'dev');
        define('ALINA_PATH_TO_FRAMEWORK', 'C:\_A001\REPOS\OWN\ALINA\_backend\alina');
        define('ALINA_PATH_TO_FRAMEWORK_CONFIG', 'C:\_A001\REPOS\OWN\ALINA\_backend\_CFG\alina\default.php');
        define('ALINA_PATH_TO_APP', ALINA_PATH_TO_FRAMEWORK);
        define('ALINA_PATH_TO_APP_CONFIG', ALINA_PATH_TO_FRAMEWORK_CONFIG);
        define('ALINA_WEB_PATH', realpath($_SERVER['DOCUMENT_ROOT']));
        define('ALINA_ENV', 'TEST');
        require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'App.php';
        $config = require(ALINA_PATH_TO_APP_CONFIG);
        $alina  = \alina\app::set($config);

        return $alina;
    }
}
