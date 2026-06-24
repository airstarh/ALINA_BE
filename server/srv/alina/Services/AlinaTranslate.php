<?php

namespace alina\Services;

use alina\GlobalRequestStorage;
use alina\mvc\Model\voc;
use alina\traits\Singleton;
use Throwable;

class AlinaTranslate
{
    use Singleton;

    public voc $voc;
    public $dict;

    public function __construct()
    {
        $this->voc  = new voc();
        $this->dict = $this->voc->q()->get()->keyBy('from');
    }

    public function t(?string $str, ?string $loc = 'ru_RU')
    {
        if (empty($str)) {
            return '';
        }

        if (\mb_strlen($str) > 444) {
            return $str;
        }

        $loc = $loc ?? GlobalRequestStorage::obj()->get('loc');

        if (! empty($this->dict[$str]->{$loc})) {
            return $this->dict[$str]->{$loc};
        }

        try {
            $this->voc->upsertByUniqueFields([
                'from' => $str,
            ], [['from']]);
        }
        catch (Throwable $s) {
            return $str;
        }

        return $str;
    }
}
