<?php

namespace alina\mvc\model;

class qReference
{
    /***@var int[] */
    public $forIds;
    /***@var string|int */
    public $has;
    /***@var string */
    public $refParentField;
    /***@var string */
    public $mChildrenAlias;
    /***@var string */
    public $refChildrenField;
    /** @var \alina\mvc\model\_baseAlinaEloquentModel */
    public $mParent;
    /** @var \alina\mvc\model\_baseAlinaEloquentModel */
    public $mChildren;
    /** @var \alina\mvc\model\_baseAlinaEloquentModel */
    public $mGlue;
    /***@var string */
    public $pkNameOfParentInGlue;
    /***@var string */
    public $pkNameOfChildInGlue;
    /***@var array */
    public $refKeys;
    /***@var array */
    public $childrenColumns;
    /***@var array */
    public $conditions;
    /***@var array */
    public $orderBy;
    /** @var \Illuminate\Database\Query\Builder */
    public $q;

    /**
     * qReference constructor.
     * @param $mParent \alina\mvc\model\_baseAlinaEloquentModel
     * @param array $options
     * @throws \ErrorException
     */
    public function __construct($mParent, $options = [])
    {
        $this->mParent = $mParent;

        foreach ($options as $k => $v) {
            $this->{$k} = $v;
        }

        switch ($this->has) {
            case 'manyThrough' :
                $this->mChildren            = modelNamesResolver::getModelObject($this->mChildren);
                $this->mGlue                = modelNamesResolver::getModelObject($this->mGlue);
                $this->pkNameOfParentInGlue = "{$this->mParent->table}_{$this->mParent->pkName}";
                $this->pkNameOfChildInGlue  = "{$this->mChildren->table}_{$this->mChildren->pkName}";
                if (isset($this->refKeys)) {
                    if (!empty($this->refKeys)) {
                        if (is_array($this->refKeys)) {
                            if (count($this->refKeys) === 2) {
                                $this->pkNameOfParentInGlue = $this->refKeys['pkNameOfParentInGlue'];
                                $this->pkNameOfChildInGlue  = $this->refKeys['pkNameOfChildInGlue'];
                            }
                        }
                    }
                }

                if (empty($this->childrenColumns)) {
                    $this->childrenColumns = ['children.*'];
                } else {
                    $childrenColumns = [];
                    foreach ($this->childrenColumns as $col) {
                        $childrenColumns[] = "children.{$col} AS {$col}";
                    }
                    $this->childrenColumns = $childrenColumns;
                }
                break;

            case 1 :
                $this->mChildren        = modelNamesResolver::getModelObject($this->mChildren);
                $this->refParentField   = "{$this->mChildren->table}";
                $this->refChildrenField = "{$this->mChildren->table}_{$this->mChildren->pkName}";
                if (isset($this->refKeys)) {
                    if (!empty($this->refKeys)) {
                        if (is_array($this->refKeys)) {
                            if (count($this->refKeys) === 2) {
                                $this->refParentField = $this->refKeys['refParentField'];
                                $this->refChildrenField = $this->refKeys['refChildrenField'];
                            }
                        }
                    }
                }

                if (empty($this->mChildrenAlias)) {
                    $this->mChildrenAlias = $this->mChildren->table;
                }

                if (empty($this->childrenColumns)) {
                    $this->childrenColumns = ["{$this->mChildrenAlias}.*"];
                } else {
                    $childrenColumns = [];
                    foreach ($this->childrenColumns as $col) {
                        if ($col === $mParent->pkName) {$this->conditions;}
                        $childrenColumns[] = "{$this->mChildrenAlias}.{$col} AS {$this->mChildrenAlias}_{$col}";
                    }
                    $this->childrenColumns = $childrenColumns;
                }
                break;
        }
    }

    public function set($p, $v) {
        $this->{$p} = $v;
        return $this;
    }

    #region hasMany

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function qHasManyThrough()
    {
        $mParent              = $this->mParent;
        $mChildren            = $this->mChildren;
        $mGlue                = $this->mGlue;
        $pkNameOfParentInGlue = $this->pkNameOfParentInGlue;
        $pkNameOfChildInGlue  = $this->pkNameOfChildInGlue;
        $childrenColumns      = $this->childrenColumns;
        $conditions           = $this->conditions;
        $orderBy              = $this->orderBy;

        $q = $mChildren->q('children');
        $q
            ->select($childrenColumns)
            ->join("{$mGlue->table} AS glue", "children.{$mChildren->pkName}", '=', "glue.{$pkNameOfChildInGlue}")
            ->addSelect(["glue.{$mGlue->pkName} AS glue_id"])
            ->addSelect(["glue.{$pkNameOfChildInGlue} AS child_id"])
            ->addSelect(["glue.{$pkNameOfParentInGlue}  AS parent_id"])
            //->addSelect(["glue.{$pkNameOfChildInGlue} AS {$mChildren->table}_id", "glue.{$pkNameOfParentInGlue}  AS {$mParent->table}_id"])
            ->join("{$mParent->table} AS parent", "parent.{$mParent->pkName}", '=', "glue.{$pkNameOfParentInGlue}");

        $mChildren->orderByArray($orderBy);

        if (isset($this->forIds) && !empty($this->forIds)) {
            $conditions[] = ['whereIn', "glue.{$pkNameOfParentInGlue}", $this->forIds];
        }

        if ($conditions) {
            foreach ($conditions as $cond) {
                $whereType = array_shift($cond);
                call_user_func_array([$q, $whereType], $cond);
            }
        }

        return $q;
    }
    #endregion hasMany

    #region Has One (Direct Reference)
    /**
     * @return static
     */
    public function qHasOne()
    {
        $mParent          = $this->mParent;
        $mParentAlias     = $mParent->alias;
        $mChildren        = $this->mChildren;
        $mChildrenAlias   = $this->mChildrenAlias;
        $refParentField   = $this->refParentField;
        $refChildrenField = $this->refChildrenField;
        $childrenColumns  = $this->childrenColumns;
        $conditions       = $this->conditions;
        $childrenTable    = $mChildren->table;

        //ATTENTION: Supposed $q already defined and passed all necessary preparations.
        $q = $mParent->q;

        $q
            ->join("{$childrenTable} AS {$mChildrenAlias}", "{$mChildrenAlias}.{$refChildrenField}", '=', "{$mParentAlias}.{$refParentField}")
            ->addSelect($childrenColumns);

        if ($conditions) {
            foreach ($conditions as $cond) {
                $whereType = array_shift($cond);
                call_user_func_array([$q, $whereType], $cond);
            }
        }

        $this->q = $q;

        return $this;
    }
    #endregion Has One (Direct Reference)
}