<?php

namespace zero\mvc\controller;

use alina\mvc\view\html;

class AdminTests extends \alina\mvc\controller\AdminTests
{
    public function actionIndex()
    {
        $vd = [
            'Hello' => 'AdminTests',
        ];
        echo (new html)->page($vd);
    }
}
