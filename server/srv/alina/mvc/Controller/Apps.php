<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html;
use alina\Watcher;

class Apps
{
    /**
     * Summary of actionIndex
     * @return void
     */
    public function actionVue()
    {
        require_once ALINA_WEB_PATH . '/apps/vue/index.html';
        AlinaExit('frontend');
    }

    public function actionVue3video()
    {
        require_once ALINA_WEB_PATH . '/apps/vue3video/index.html';
        AlinaExit('video');
    }
}
