<?php

namespace alina\mvc\model;

use alina\utils\Request;

class watch_login extends _BaseAlinaModel
{
    public $table = 'watch_login';

    public function fields()
    {
        return [
            'id'          => [],
            'mail'        => [],
            'ip'          => [
                'default' => Request::obj()->IP,
            ],
            'browser_enc' => [
                'default' => Request::obj()->BROWSER_enc,
            ],
            'visits'      => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['mail', 'ip', 'browser_enc'],
        ];
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        if (property_exists($this->attributes, 'visits')) {
            $dataArray['visits'] = $this->attributes->visits + 1;
        }

        return $this;
    }
}
