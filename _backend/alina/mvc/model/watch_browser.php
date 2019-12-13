<?php

namespace alina\mvc\model;

use alina\utils\Request;

class watch_browser extends _BaseAlinaModel
{
    public $table = 'watch_browser';

    public function fields()
    {
        return [
            'id'         => [],
            'user_agent' => [],
            'enc'        => [],
            'visits'     => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['user_agent'],
        ];
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        $dataArray['enc'] = Request::obj()->BROWSER_enc;

        if (property_exists($this->attributes, 'visits')) {
            $dataArray['visits'] = $this->attributes->visits +1;
        }

        return $this;
    }
}
