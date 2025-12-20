<?php

namespace alina\Services;

use alina\mvc\Model\voc;
use alina\traits\Singleton;

class AlinaTranslate
{
    use Singleton;

    public voc $voc;
    public     $dict;

    public function __construct()
    {
        $this->voc  = new voc();
        $this->dict = $this->voc->q()->get()->keyBy('from');
    }

    public function t($str, $loc = 'ru_RU')
    {
        if (empty($str)) return null;
        if (!empty($this->dict[$str]->{$loc})) return $this->dict[$str]->{$loc};
        $this->voc->upsertByUniqueFields([
            'from' => $str,
        ], [['from']]);
        return $str;
    }
}