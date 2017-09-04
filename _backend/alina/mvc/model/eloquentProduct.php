<?php
namespace alina\mvc\model;

class eloquentProduct extends eloquentModel
{
    protected $table      = 'product';
    protected $primaryKey = 'id';
    protected $guarded    = [];
    protected $dateFormat = 'U';
    public    $timestamps = FALSE;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


}