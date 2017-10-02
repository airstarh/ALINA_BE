<?php
namespace alina\mvc\model\eloquent\eav;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;
use \alina\mvc\model\eloquent\trait_common_definitions;

class eav extends BaseEloquentModel
{
    protected $table      = 'eav';
    use trait_common_definitions;

    protected $allTypes = [
        'value_varchar_500',
        'value_int_11',
    ];

    #region EAV

    public function ent()
    {
        return $this->morphTo('ent', 'ent_table', 'ent_id');
    }

    public function val()
    {
        return $this->morphTo('val', 'val_table', 'val_id');
    }

    #endregion EAV
}