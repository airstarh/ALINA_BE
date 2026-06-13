<?php

namespace stage\mvc\Controller;

use alina\mvc\View\html;

class AdminTests extends \alina\mvc\Controller\AdminTests
{
    public function actionIndex()
    {
        $vd = [
            'Hello' => 'AdminTests',
        ];
        echo (new html())->page($vd);
    }
}
