<?php
/**
 * Created by PhpStorm.
 * User: ladmin
 * Date: 05.10.2017
 * Time: 16:24
 */

namespace alina\mvc\model\eloquent;


trait trait_eloquent
{
    public function x()
    {
        $t  = $this->table;
        $pk = $this->primaryKey;
        $id = $this->{$pk};

        $x     = new \stdClass();
        $x->t  = $t;
        $x->pk = $pk;
        $x->id = $id;

        return $x;
    }
}