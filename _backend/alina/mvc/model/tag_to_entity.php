<?php

namespace alina\mvc\model;

class tag_to_entity extends _BaseAlinaModel
{
    public $table = 'tag_to_entity';

    public function fields()
    {
        return [
            'id'  => [],
            'entity_id'  => [],
            'entity_table'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['entity_id', 'entity_table']
        ];
    }
}