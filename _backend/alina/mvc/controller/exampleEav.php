<?php

namespace alina\mvc\controller;


class exampleEav
{
    public function actionGetAttr()
    {
        $res    = [];
        $mClass = '\alina\mvc\model\eloquentProduct';
        $m      = $mClass::find(1);
        $res    = array_merge($res, $m->toArray());

//        echo '<pre>';
//        print_r($res->ent_attr->toArray());
//        echo '</pre>';

        $eav = [];
        foreach ($m->ent_attr as $EntAttr) {
            $ea = $EntAttr->toArray();
            $attr = $EntAttr->attr->toArray();
            $eav[] = array_merge($ea,$attr);
        }
        $res['eav'] = $eav;

        echo '<pre>';
        print_r($res);
        echo '</pre>';


    }
}