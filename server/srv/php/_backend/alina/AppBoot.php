<?php

namespace alina;

/**
 * This File exists mostly for Unit Test needs.
 *This file DOES NOT participate in Application usage!!!
 */
class AppBoot
{
    static public function initiate()
    {
        ##################################################
        #region COMMON FOR ALL
        define('ALINA_MICROTIME', $_SERVER['REQUEST_TIME_FLOAT'] ?: microtime(TRUE));
        define('ALINA_TIME', $_SERVER['REQUEST_TIME'] ?: time());
        define('ALINA_COOKIE_PAST', ALINA_TIME - 60 * 60);
        define('ALINA_MAX_TIME_DIFF_SEC', 48 * 60 * 60);
        define('ALINA_MIN_TIME_DIFF_SEC', 30);
        define('ALINA_AUTH_EXPIRES', ALINA_TIME + ALINA_MAX_TIME_DIFF_SEC);
        #endregion COMMON FOR ALL
        ##################################################
        #region HOST SPECIFIC
        define('ALINA_WEB_PATH', realpath($_SERVER['DOCUMENT_ROOT']));
        define('ALINA_MODE', 'DEV');
        #####
        define("ALINA_BACKEND", 'C:/_A001/REPOS/OWN/ALINA/_backend');
        define('ALINA_PATH_TO_APP', ALINA_BACKEND . '/alina');
        #endregion HOST SPECIFIC
        ##################################################
        #region AUTOMATIC
        define("ALINA_PATH_TO_FRAMEWORK", ALINA_BACKEND . '/alina');
        define("ALINA_PATH_TO_FRAMEWORK_CONFIG", ALINA_PATH_TO_FRAMEWORK . '/cfg/default.php');
        define("ALINA_PATH_TO_APP_CONFIG", ALINA_PATH_TO_APP . '/cfg/default.php');
        #endregion AUTOMATIC
        ##################################################
        require_once ALINA_PATH_TO_FRAMEWORK . DIRECTORY_SEPARATOR . 'App.php';
        $config = require(ALINA_PATH_TO_APP_CONFIG);
        $alina  = \alina\app::set($config);

        return $alina;
    }
}
