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

    public function actionGetAttr()
    {
        /**@var $m \alina\mvc\model\eloquent\_base */

        $res    = [];
        $mClass = '\alina\mvc\model\eloquentProduct';
        $m      = $mClass::find(1);


        $m->attributes();

        $m->getAttributes(['tag', 'comment'])->values();

        $m->setAttributesValues([
            'tag'  => ['#default', '#yo'],
            'rate' => [100],
        ]);

        $m->resetAttributesValues([
            'tag'  => ['#myHashTag', '#alina'],
            'rate' => [146],
        ]);

        $m->getValues(['tag']);
        $m->getValues(['tag', 'comment']);

        $m->setAttribute('temperature', [36.6], 'value_temperature_celsius');

        $m->getFullEavState();

        $m->eavWhere('temperature', '=', '36.6', 'and', 'value_temperature_celsius');

        $eav = [];
        foreach ($m->ent_attr as $EntAttr) {
            $ea    = $EntAttr->toArray();
            $attr  = $EntAttr->attr->toArray();
            $eav[] = array_merge($ea, $attr);
        }
        $res['eav'] = $eav;

        echo '<pre>';
        print_r($res);
        echo '</pre>';


    }
}