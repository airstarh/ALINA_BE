<?php

namespace alina\mvc\controller;


class egEav
{
    public function actionIndex(){
        /**@var $m \alina\mvc\model\eloquent\_base */

        $mClass = '\alina\mvc\model\eloquent\product';
        $m      = $mClass::find(1);
        $m->eavGetValues('temperature', 'age', 'not_existant_value');
    }

    public function actionSetValue()
    {
        /**@var $m \alina\mvc\model\eloquent\_base */

        $mClass = '\alina\mvc\model\eloquent\product';
        $m      = $mClass::find(1);
        $m->eavSetValue('temperature', [35.1, 36.6, 37.2, time()]);
    }


    /**Checked*/
    public function actionAddAttr()
    {
        /**@var $m \alina\mvc\model\eloquent\_base */

        $mClass = '\alina\mvc\model\eloquent\product';
        $m      = $mClass::find(1);

        $m->eavAddAttribute([
            'name_sys'          => 'age',
            'name_human'        => 'age',
            'val_table' => 'value_int_11',
            'order'             => 1,
            'quantity'          => 100,
        ]);

        echo '<pre>';
        print_r($m);
        echo '</pre>';
    }
}