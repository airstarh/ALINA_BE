<?php
namespace alina\mvc\model\eloquent\eav;

use \alina\mvc\model\eloquent\_base AS BaseEloquentModel;

class ea extends BaseEloquentModel
{
    protected $table = 'ea';

    protected $allTypes = [
        'value_varchar_500',
        'value_int_11',
    ];

    public function fields()
    {
        return [
            'order' => [
                'default' => 1,
            ],
            'quantity' => [
                'default' => 1,
            ],
        ];
    }

    #region EAV
    public function ent()
    {
        return $this->morphTo('ent', 'ent_table', 'ent_id');
    }
    #endregion EAV

    #region Attributes
    protected $entity;
    protected $entId;
    protected $entTable;

    public function attributesOfEntity($entity)
    {
        $this->setEntity($entity);
        $this->where('ent_id', '=', $this->entId);
        $this->where('ent_table', '=', $this->entTable);
    }

    /**
     * @param $entity \alina\mvc\model\eloquent\_base
     * @return object
     */
    public function setEntity($entity)
    {
        $this->entity   = $entity;
        $this->entId    = $entity->x()->id;
        $this->entTable = $entity->x()->t;

        return $this;
    }
    #endregion Attributes
}