<?php
namespace alina\mvc\model\eloquent\eav;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;

class eav extends BaseEloquentModel
{
    protected $table      = 'eav';

    protected $oE;
    protected $oA;
    protected $oV;
    protected $oEa;





    #region EAV
    public function val()
    {
        return $this->morphTo('val', 'val_table', 'val_id');
    }


    public function setEa($oEa) {$this->oEa = $oEa;return $this;}


    #endregion EAV
}