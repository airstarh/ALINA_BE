<?php

namespace alina\mvc\model\eloquent\eav;

trait trait_entity
{

    public function eavAddAttribute($a)
    {
        $oA             = new attr();
        $oA->name_sys   = $a['name_sys'];
        $oA->name_human = $a['name_human'];

//        echo '<pre>';
//        print_r($oA);
//        echo '</pre>';
//        exit();


        $oA->prepareModel();
        $oA->save();

        $oEa                    = new ea();
        $oEa->attr_id           = $oA->{$oA->primaryKey};
        $oEa->ent_id            = $this->{$this->primaryKey};
        $oEa->ent_table         = $this->table;
        $oEa->quantity          = $a['quantity'];
        $oEa->order             = $a['order'];
        $oEa->val_default_table = $a['val_default_table'];
        $oEa->save();

        $this->s('oA', $oA);
        $this->s('oEa', $oEa);

        return $this;
    }

    public function eavSetValue($nameSys, $value, $valueTable)
    {
        $oA = new attr();
        $oA->where('name_sys', '=', $nameSys)->get();
    }










    public function eavWhere($column, $operator = NULL, $value = NULL, $boolean = 'and', $valueTable)
    {
        $oAttr = new attr();
        $oAttr->where('name_sys', '=', '$column');
        $oE   = $this;
        $oEa  = new ea();
        $oEav = new eav();

        $oE
            ->joinEav();
    }

    #region EAV

    public function attributes()
    {

        return $this->morphMany('\alina\mvc\model\eloquent\eav\eav', 'ent', 'ent_table', 'ent_id', 'id');
    }

//    public function attr()
//    {
//        return $this->morphToMany('\alina\mvc\model\eloquent\eav\attr', 'eav', 'eav', );
//    }
    #endregion EAV

    #region Getters Setters
    #Attributes
    public $eavTable = 'eav';

    public function getAttributes()
    {
        $this->joinEav();
    }

    public function joinEav()
    {
        $oE = $this;
        $this->join('eav', function ($join) use ($oE) {

            $join
                ->on("{$oE->table}.{$oE->primaryKey}", '=', "ent.ent_id")
                ->andOn("ent.ent_table", '=', "{$oE->table}");
        });

        return $this;
    }

    public function modifyAttribute() { }

    public function deleteAttribute() { }

    #Attribute Values
    public function getAttributeValues() { }

    public function setAttributeValues() { }

    public function modifyAttributeValues() { }

    public function deleteAttributeValues() { }
    #endregion Getters Setters
}