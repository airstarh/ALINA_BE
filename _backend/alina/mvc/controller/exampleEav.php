<?php

namespace alina\mvc\controller;


use \alina\mvc\model\eloquentProduct as product;

class exampleEav
{
    public function actionGetAttr()
    {
        $mClass = '\alina\mvc\model\eloquentProduct';
        $res = product::find(1);

        echo '<pre>';
        print_r($res);
        echo '</pre>';

        echo '<pre>';
        print_r($res->ent_attr->toArray());
        echo '</pre>';
    }
}