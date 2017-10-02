<?php

namespace alina\mvc\model\eloquent;

trait trait_common_definitions
{
    protected $primaryKey = 'id';
    protected $guarded    = [];
    protected $dateFormat = 'U';
    public    $timestamps = FALSE;
    //const CREATED_AT = 'created_at';
    //const UPDATED_AT = 'updated_at';
}