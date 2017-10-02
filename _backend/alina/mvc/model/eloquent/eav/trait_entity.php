<?php

namespace alina\mvc\model\eloquent\eav;

trait trait_entity
{
    #region EAV

    public function attributes()
    {

        return $this->morphMany('\alina\mvc\model\eloquent\eav\eav', 'ent', 'ent_table', 'ent_id', 'id');
    }

    #endregion EAV

    #region Getters Setters
    #Attributes
    public function getAttributes() {}
    public function modifyAttribute() {}
    public function deleteAttribute() {}

    #Attribute Values
    public function getAttributeValues() {}
    public function setAttributeValues() {}
    public function modifyAttributeValues() {}
    public function deleteAttributeValues() {}
    public function addAttribute() {}

    #endregion Getters Setters
}