<?php

namespace alina\Services;

use alina\GlobalRequestStorage;
use alina\mvc\Model\voc;
use alina\traits\Singleton;
use alina\Utils\Data;
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
        $this->LANGUAGE = $this->discoverLanguage();
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

    private function discoverLanguage()
    {
        $default = 'ru_RU';

        return Data::getFirstNonEmpty(
            [
            Request::obj()->R->LANGUAGE ?? null, // GIT POST COOKIE
            Request::obj()->tryHeader('LANGUAGE'),
            substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2),
            $default,
        ]
        );
    }
}
