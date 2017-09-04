<?php
namespace alina\mvc\model;

class eloquentAttrVal extends eloquentModel
{
    protected $table      = 'attr_varchar_500';
    protected $primaryKey = 'id';
    protected $guarded    = [];
    protected $dateFormat = 'U';
    public    $timestamps = FALSE;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function ent_attr()
    {
        return $this->belongsTo('\alina\mvc\model\eloquentEntAttr', 'ent_attr_id', 'id', 'ent_attr');
    }
}