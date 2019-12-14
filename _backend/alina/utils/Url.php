<?php

namespace alina\utils;

use alina\message;

class Url
{
    ##################################################
    #region URL's, Aliases, Routes
    static public function routeAccordance($url, array $vocabulary = [], $aliasToSystemRoute = TRUE)
    {
        $parsedUrlSource = parse_url($url);
        $pathSource      = $parsedUrlSource['path'];
        $pathSource      = trim($pathSource, '/');
        $pathRes         = '';

        foreach ($vocabulary as $aliasMask => $urlMask) {

            $compareWith       = ($aliasToSystemRoute) ? $aliasMask : $urlMask;
            $regularExpression = static::routeRegExp($compareWith);

            if (preg_match($regularExpression, $pathSource)) {
                if ($aliasToSystemRoute) {
                    $pathRes = static::aliasToUrl($aliasMask, $pathSource, $urlMask);
                } else {
                    $pathRes = static::urlToAlias($urlMask, $pathSource, $aliasMask);
                }
                $parsedUrlSource['path'] = $pathRes;
                $uri = static ::un_parse_url($parsedUrlSource);
                return $uri;
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
    ##################################################
    static public function un_parse_url(array $parsedUri)
    {
        $get = function ($key) use ($parsedUri) {
            return isset($parsedUri[$key]) ? $parsedUri[$key] : '';
        };

        $pass         = $get('pass');
        $user         = $get('user');
        $userinfo     = (!empty($pass)) ? "$user:$pass" : $user;
        $port         = $get('port');
        $scheme       = $get('scheme');
        $query        = $get('query');
        $fragment     = $get('fragment');
        $arrAuthority = [
            !empty($userinfo) ? "$userinfo@" : '',
            $get('host'),
            $port ? ":$port" : '',
        ];
        $authority    = implode('', $arrAuthority);

        $arrRes = [
            strlen($scheme) ? "$scheme:" : '',
            strlen($authority) ? "//$authority" : '',
            $get('path'),
            strlen($query) ? "?$query" : '',
            strlen($fragment) ? "#$fragment" : '',
        ];

        return implode('', $arrRes);
    }

    static public function cleanPath($url)
    {
        $res = $url;
        $res = parse_url($res, PHP_URL_PATH);
        $res = urldecode($res);
        //$res = mb_strtolower($res);

        return $res;
    }
}
