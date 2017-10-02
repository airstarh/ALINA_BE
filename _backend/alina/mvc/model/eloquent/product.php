<?php
namespace alina\mvc\model\eloquent;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;

class product extends BaseEloquentModel
{
    protected $table      = 'product';
    protected $primaryKey = 'id';
    protected $guarded    = [];
    protected $dateFormat = 'U';
    public    $timestamps = FALSE;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


}