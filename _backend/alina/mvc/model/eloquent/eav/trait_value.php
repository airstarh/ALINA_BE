<?php

namespace alina\mvc\model\eloquent\eav;

trait trait_value
{
    #region EAV

    #region EAV

    public function vals()
    {

        return $this->morphMany('\alina\mvc\model\eloquent\eav\eav', 'val', 'val_table', 'val_id', 'id');
    }

    #endregion EAV

    #endregion EAV
}