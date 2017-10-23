<?php

namespace alina\mvc\model\eloquent\eav;

trait trait_entity
{
    #region Add Attribute

    public function eavAddAttribute($a)
    {
        //ToDo: Validate $a, prepare $a.
        $a = toObject($a);

        $oA             = new attr();
        $oA->name_sys   = $a->name_sys;
        $oA->name_human = $a->name_human;
        $oA->val_table  = $a->val_table;
        $oA->prepareModel();
        $oA->save();

        $attributeId = $oA->{$oA->primaryKey};
        $entityId    = $this->{$this->primaryKey};
        $entityTable = $this->table;

        $oEa            = new ea();
        $oEa->attr_id   = $attributeId;
        $oEa->ent_id    = $entityId;
        $oEa->ent_table = $entityTable;
        $oEa->quantity  = $a->quantity;
        $oEa->order     = $a->order;
        $oEa->prepareModel();
        $oEa->save();

        return $this;
    }

    #endregion Add Attribute

    #region Add Set Value

    public function eavSetValue($nameSys, $values)
    {
        $return = [];
        $values = is_array($values) ? $values : [$values];

        $oEaA      = $this->getEaA($nameSys)[0];
        $ea_id     = $oEaA->ea_id;
        $val_table = $oEaA->val_table;

        //ToDo: Make deletion more accurate.
        $oV = new val();
        $oV->setTable($val_table)->where('ea_id', $ea_id)->forceDelete();
        unset($oV);

        $vIds = [];
        foreach ($values as $v) {
            $oV = new val();
            $oV->setTable($val_table);
            $oV->ea_id = $ea_id;
            $oV->val   = $v;
            $oV->save();
            $vIds[]   = $oV->{$oV->primaryKey};
            $return[] = $oV;
        }
        unset($oV);

        echo '<pre>';
        print_r($return);
        //print_r(func_get_args());
        echo '</pre>';
    }

    public function getEaA($attrSysNames = [])
    {
        $attrSysNames = is_array($attrSysNames) ? $attrSysNames : [$attrSysNames];
        $entityId     = $this->{$this->primaryKey};
        $entityTable  = $this->table;
        $oEaA         = new attr();
        $q            = $oEaA
            ->select(
                'a.*',
                'ea.id as ea_id',
                'ea.quantity',
                'ea.order'
            )
            ->from('attr as a')
            ->join('ea as ea', 'ea.attr_id', '=', 'a.id')
            ->where('ea.ent_id', '=', $entityId)
            ->where('ea.ent_table', '=', $entityTable);

        if ($attrSysNames && !empty($attrSysNames)) {
            $q = $q->whereIn('a.name_sys', $attrSysNames);
        }

        $cEaA = $q->get();

        return $cEaA;
    }
    #emdregion Add Set Value

    #region Get Values
    public function eavGetValues($attrSysNames = [], $whereArray = [])
    {
        $attrSysNames = is_array($attrSysNames) ? $attrSysNames : [$attrSysNames];
        $entityId     = $this->{$this->primaryKey};
        $entityTable  = $this->table;
        $oAeA         = new attr();
        $q            = $oAeA
            ->select(
                'a.*',
                'ea.id as ea_id',
                'ea.ent_table',
                'ea.quantity',
                'ea.order'
            )
            ->from('attr as a')
            ->join('ea as ea', 'ea.attr_id', '=', 'a.id')
            ->where('ea.ent_id', '=', $entityId)
            ->where('ea.ent_table', '=', $entityTable);

        if ($attrSysNames && !empty($attrSysNames)) {
            $q = $q->whereIn('a.name_sys', $attrSysNames);
        }
        $cEavEaA = $q->get();

        return $cEavEaA;
    }

    public function eavGetAttrValues($attrNameSys)
    {
        $attrNamesSys = func_get_args();
    }
    #endregion Get Values
}