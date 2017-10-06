<?php
namespace alina\mvc\model\eloquent\eav;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;
use \alina\mvc\model\eloquent\eav\trait_value;

class val extends BaseEloquentModel
{
    protected $table = 'value_varchar_500';
    use trait_value;
}