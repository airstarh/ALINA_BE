<?php

namespace m45a\mvc\Controller;

use alina\mvc\View\html;

class Main
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function action404()
    {
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new html())->page((object) [
            'pageNotFound' => ___('Page not found'),
        ]);
        exit();
    }
}
