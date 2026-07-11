<?php

namespace alina\Services;

use alina\GlobalRequestStorage;
use alina\mvc\Model\voc;
use alina\traits\Singleton;
use alina\Utils\Request;
use Throwable;

class AlinaTranslate
{
    use Singleton;

    private string $LANGUAGE = 'ru_RU';
    public voc $voc;
    public $dict;

    public function __construct()
    {
        $this->LANGUAGE = Request::obj()->LANGUAGE;
        $this->voc      = new voc();
        $this->dict     = $this->voc->q()->get()->keyBy('from');
    }

    public function t(?string $str, ?string $language = 'ru_RU')
    {
        if (empty($str)) {
            return '';
        }

        if (\mb_strlen($str) > 444) {
            return $str;
        }

        $language = $language ?? $this->LANGUAGE;

        if (! empty($this->dict[$str]->{$language})) {
            return $this->dict[$str]->{$language};
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
