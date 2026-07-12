<?php

namespace alina\Services;

use alina\GlobalRequestStorage;
use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\voc;
use alina\traits\Singleton;
use alina\Utils\Data;
use alina\Utils\Request;
use Throwable;

class AlinaTranslate
{
    use Singleton;

    private string $LANGUAGE = 'ru_RU';
    private $dict;
    private voc $voc;
    private array $vocLocales = [
        'en' => 'en_US',
        'ru' => 'ru_RU',
    ];

    public function __construct()
    {
        $this->LANGUAGE = $this->discoverLanguage();
        $this->voc      = new voc();
        $this->dict     = $this->voc->q()->get()->keyBy('from');
    }

    public function t(?string $str, ?string $language = null)
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
        $default = $this->LANGUAGE;
        $tmp     = Data::getFirstNonEmpty(
            [
            CurrentUser::obj()->language(),
            Request::obj()->R->LANGUAGE ?? null, // GIT POST COOKIE
            Request::obj()->tryHeader('LANGUAGE'),
            //ToDo: Translations from browser locale are not robust.
            // substr(Request::obj()->SERVER->HTTP_ACCEPT_LANGUAGE ?? '', 0, 2),
            $default,
        ]
        );

        if (strlen((string)($tmp ?? '')) === 2) {
            $tmp = $this->vocLocales[$tmp] ?? $this->LANGUAGE;
        }

        return $tmp;
    }
}
