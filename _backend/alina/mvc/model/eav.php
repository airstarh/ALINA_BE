<?php

namespace alina\mvc\model;


trait eav
{
    #region EAV
    protected $eavModelAttr = '\alina\mvc\model\eloquentEntAttr';
    protected $eavModelVal  = '\alina\mvc\model\eloquentAttrVal';

    public function ent_attr()
    {
        return $this->morphMany($this->eavModelAttr, 'ent_attr', 'ent_table', 'ent_id', 'id');
    }
    #endregion EAV
}