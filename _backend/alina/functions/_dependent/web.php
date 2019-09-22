<?php

function aRef($url)
{
    if (\alina\utils\Str::startsWith($url, 'http://')
        || \alina\utils\Str::startsWith($url, 'https://')
    ) return $url;

    $vocAliasToUrl = \alina\app::getConfig(['vocAliasUrl']);
    $url  = \alina\utils\Url::routeAccordance(ltrim($url, '/'), $vocAliasToUrl, false);

    return "//{$_SERVER['HTTP_HOST']}/{$url}";
}

function aL($ref, $text = '', $configuration = array())
{
    $href     = '';
    $get      = '';
    $getArray = array();
    $hash     = '';

    if (isset($configuration['get']) && !empty($configuration['get'])) {
        foreach ($configuration['get'] as $parameterName => $parameterValue) {
            $getArray[] = "$parameterName=$parameterValue";
        }
        $get = '?' . implode('&', $getArray);
        unset($configuration['get']);
    }

    if (isset($configuration['hash']) && !empty($configuration['hash'])) {
        $hash = '#' . $configuration['hash'];
        unset($configuration['hash']);
    }

    $href .= aRef($ref) . $get . $hash;

    $configuration['href'] = $href;
    return \alina\utils\Html::tag('a', $text, $configuration);
}
