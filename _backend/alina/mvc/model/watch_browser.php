<?php

namespace alina\mvc\model;

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
        $dataArray['enc'] = md5($dataArray['user_agent']);

        if (isset($this->attributes->visits)) {
            $dataArray['visits'] = $this->attributes->visits +1;
        }

        return $this;
    }
}
