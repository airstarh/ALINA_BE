<?php

namespace alina\mvc\model;

class watch_url_path extends _BaseAlinaModel
{
    public $table = 'watch_url_path';

    public function fields()
    {
        return [
            'id'         => [],
            'url_path' => [],
            'visits'     => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['url_path'],
        ];
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        if (isset($this->attributes->visits)) {
            $dataArray['visits'] = $this->attributes->visits +1;
        }

        return $this;
    }
}
