<?php

namespace sss\mvc\Controller;

class Main
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function action404()
    {
        echo '<pre>';
        print_r('<h1>404</h1>Страница не найдена');
        echo '</pre>';
    }
}
