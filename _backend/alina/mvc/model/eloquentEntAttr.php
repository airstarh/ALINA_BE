<?php
namespace alina\mvc\model;

class eloquentEntAttr extends eloquentModel
{
    protected $table      = 'ent_attr';
    protected $primaryKey = 'id';
    protected $guarded    = [];
    protected $dateFormat = 'U';
    public    $timestamps = FALSE;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $allTypes = [
        'attr_varchar_500',
        'attr_integer',
    ];

    #region EAV
    public function ent()
    {
        return $this->morphTo('ent', 'ent_table', 'ent_id');
    }

    public function attrVal()
    {
        return $this->hasMany('\alina\mvc\model\eloquentAttrVal', 'ent_attr_id', 'id');
    }
    #endregion EAV
}