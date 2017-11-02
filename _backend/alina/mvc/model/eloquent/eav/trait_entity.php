<?php

namespace alina\mvc\model\eloquent\eav;

use \alina\vendorExtend\illuminate\alinaLaravelCapsule as Dal;

trait trait_entity
{
    public $cV = [];
    public $cA = [];

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

        foreach ($values as $v) {
            $oV = new val();
            $oV->setTable($val_table);
            $oV->ea_id = $ea_id;
            $oV->val   = $v;
            $oV->save();
            $return[] = $oV;
        }
        unset($oV);

        return $return;
    }

    public function getEaA($attrSysNames = [], $forThisEntity = TRUE)
    {
        $attrSysNames = is_array($attrSysNames) ? $attrSysNames : [$attrSysNames];
        $entityId     = $this->{$this->primaryKey};
        $entityTable  = $this->table;
        $oEaA         = new attr();
        $q            = $oEaA
            ->select(
                'a.*',
                'a.id as attr_id',
                'ea.id as ea_id',
                'ea.quantity',
                'ea.order'
            )
            ->from('attr as a')
            ->join('ea as ea', 'ea.attr_id', '=', 'a.id')
            ->where('ea.ent_table', '=', $entityTable);

        if ($forThisEntity) {
            $q = $q->where('ea.ent_id', '=', $entityId);
        }

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
        $cEaA         = $this->getEaA($attrSysNames);

        $sequenceValTableEaId = [];
        foreach ($cEaA as $i => $mEaA) {
            $sequenceValTableEaId[$mEaA->val_table][] = $mEaA->ea_id;
        }

        $arrValues = [];
        foreach ($sequenceValTableEaId as $valTable => $eaIds) {
            $oVal = new val();
            $q    = $oVal
                ->setTable($valTable)
                ->whereIn('ea_id', $eaIds);
            //ToDo: Add more WHERE abilities.
            $cVal                 = $q->get();
            $arrValues[$valTable] = $cVal;
        }

        return $arrValues;
    }

    /**
     * [
     *  [atrSysName, =, value]
     *  [atrSysName, <, value]
     *  [atrSysName, LIKE, %value%]
     *  [atrSysName, IN, [ARRAY] ]
     * ]
     */
    public function eavGetAllWhere($whereArray)
    {
        $thisTableName = $this->getTable();

        $q = Dal::table($thisTableName)
            ->select("{$thisTableName}.*")
            ->join("ea", "ea.ent_id", '=', "{$thisTableName}.id")
            ->join("attr", "attr.id", '=', "ea.attr_id");

        $attrSysNames = [];
        foreach ($whereArray as $where) {
            $attrSysNames[] = $where[0];

            $nameSys  = $where[0];
            $operator = $where[1];
            $compare  = $where[2];

            switch ($operator) {

                case 'in':
                case 'IN':
                    $q = $q->whereIn("{$nameSys}.val", $compare);
                    break;

                case '=':
                default:
                    $q = $q->where("{$nameSys}.val", $operator, $compare);
                    break;
            }
        }

        $cEaA = $this->getEaA($attrSysNames, FALSE);

        foreach ($cEaA as $i => $mEaA) {
            $t       = $mEaA->val_table;
            $nameSys = $mEaA->name_sys;
            $q       = $q->addSelect("{$nameSys}.val as {$nameSys}");
            $q       = $q->join("{$t} as {$nameSys}", "{$nameSys}.ea_id", '=', "ea.id");
        }

        $cEntity = $q->get();

        return $cEntity;
    }
    #endregion Get Values
}