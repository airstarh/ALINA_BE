<?php
namespace alina\mvc\model\eloquent\eav;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;
use \alina\mvc\model\eloquent\trait_common_definitions;

class attr extends BaseEloquentModel
{
    protected $table      = 'attr';
    use trait_common_definitions;
}