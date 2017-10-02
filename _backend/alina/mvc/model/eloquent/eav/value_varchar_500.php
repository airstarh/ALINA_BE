<?php
namespace alina\mvc\model\eloquent\eav;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;
use \alina\mvc\model\eloquent\trait_common_definitions;
use \alina\mvc\model\eloquent\eav\trait_value;

class value_varchar_500 extends BaseEloquentModel
{
    protected $table = 'value_varchar_500';

    use trait_common_definitions;
    use trait_value;
}