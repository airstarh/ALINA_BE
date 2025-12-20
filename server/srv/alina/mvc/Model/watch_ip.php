<?php

namespace alina\mvc\Model;
class watch_ip extends _BaseAlinaModel
{
    public $table = 'watch_ip';

    public function fields()
    {
        return [
            'id'     => [],
            'ip'     => [],
            'visits' => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['ip'],
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
