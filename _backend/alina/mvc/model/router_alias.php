<?php

namespace alina\mvc\model;
class router_alias extends _BaseAlinaModel
{
    public $table = 'router_alias';

    public function fields()
    {
        return [
            'id'       => [],
            'alias'    => [],
            'url'      => [],
            'table'    => [],
            'table_id' => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['alias'],
        ];
    }

    public function getAsVoc()
    {
        $res        = [];
        $collection =
            $this
                ->q()
                ->select(['alias', 'url'])
                ->get();
        foreach ($collection as $i => $v) {
            $res["{$v->alias}"] = $v->url;
        }

        return $res;
    }
}
