<?php

namespace alina\mvc\controller;


class egEav
{
    public function actionGetAllWhere()
    {
        /**@var $m \alina\mvc\model\eloquent\_base */

        $mClass = '\alina\mvc\model\eloquent\product';
        //$m      = $mClass::find(1);
        $m = new $mClass();
        $res = $m->eavGetAllWhere([
            ['temperature', '=', 1],
            ['age', '>', 10],
        ]);

        echo '<pre>';
        print_r($res);
        echo '</pre>';
    }

    public function actionSetValue()
    {
        /**@var $m \alina\mvc\model\eloquent\_base */

        $mClass = '\alina\mvc\model\eloquent\product';
        $m      = $mClass::find(1);
        //$res = $m->eavSetValue('temperature', [1, 1, 1, time()]);
        $res = $m->eavSetValue('age', [5, 10, 15, -33, time()]);
        echo '<pre>';
        print_r($res);
        echo '</pre>';
    }


    /**Checked*/
    public function actionAddAttr()
    {
        /**@var $m \alina\mvc\model\eloquent\_base */

        $mClass = '\alina\mvc\model\eloquent\product';
        $m      = $mClass::find(1);

        $m->eavAddAttribute([
            'name_sys'   => 'description',
            'name_human' => 'description',
            'val_table'  => 'value_varchar_500',
            'order'      => 1,
            'quantity'   => 3,
        ]);

        echo '<pre>';
        print_r($m);
        echo '</pre>';
    }
}