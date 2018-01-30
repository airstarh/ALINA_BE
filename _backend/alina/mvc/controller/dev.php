<?php

namespace alina\mvc\controller;


class dev
{
    public function actionIndex()
    {

        $o1 = new \stdClass();
        $o2 = new \stdClass();

        $o1->o1prop1  = 'Hello';
        $o1->o1prop2  = 'World';
        $o1->common  = 'FROM o1';

        $o2->o2prop1  = 'o2 property Привет';
        $o2->o2prop2  = 'o2 property  Мир';
        $o2->common  = 'FROM o2';

        echo '<pre>';
        print_r(mergeSimpleObjects($o1, $o2));
        echo '</pre>';

        return;
    }
}