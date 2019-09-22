<?php

namespace alina\utils;

class Url
{
#region URL's, Aliases, Routes
    static public function routeAccordance($url, array $vocabulary = [], $aliasToSystemRoute = TRUE)
    {
        foreach ($vocabulary as $aliasMask => $urlMask) {

            $compareWith       = ($aliasToSystemRoute) ? $aliasMask : $urlMask;
            $regularExpression = static::routeRegExp($compareWith);

            if (preg_match($regularExpression, $url)) {

                if ($aliasToSystemRoute) {
                    return static::aliasToUrl($aliasMask, $url, $urlMask);
                } else {
                    return static::urlToAlias($urlMask, $url, $aliasMask);
                }
            }
        }

        return $url;
    }

    static public function routeRegExp($string)
    {
        $parts = explode('/', $string);

        $regularExpression = [];
        foreach ($parts as $v) {
            if ($v === ':p' || FALSE !== strpos($v, ':p')) {
                $regularExpression[] = '.+?';
            } else {
                $regularExpression[] = $v;
            }
        }
        $regularExpression = implode('\/', $regularExpression);
        $regularExpression = '/^' . $regularExpression . '$/i';

        return $regularExpression;
    }

    static public function aliasToUrl($aliasMask, $systemRoute, $systemRouteMask)
    {
        return static::routeConverter(
            $aliasMask,
            $systemRoute,
            $systemRouteMask
        );
    }

    static public function urlToAlias($systemRouteMask, $systemRoute, $aliasMask)
    {
        return static::routeConverter(
            $systemRouteMask,
            $systemRoute,
            $aliasMask
        );
    }

    static public function routeConverter($fromMask, $source, $toMask)
    {
        $fromMaskArray = explode('/', $fromMask);
        $sourceArray   = explode('/', $source);
        $toMaskArray   = explode('/', $toMask);

        $_parameters = [];
        foreach ($fromMaskArray as $i => $pN) {
            if (FALSE !== strpos($pN, ':p')) {
                $_parameters[$pN] = $sourceArray[$i];
            }
        }

        $convertedResult = [];
        foreach ($toMaskArray as $i => $pN) {
            if (FALSE !== strpos($pN, ':p')) {
                $convertedResult[] = $_parameters[$pN];
            } else {
                $convertedResult[] = $pN;
            }
        }

        return implode('/', $convertedResult);
    }

#endregion URL's, Aliases, Routes
}
