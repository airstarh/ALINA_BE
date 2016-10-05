<?php

function aRef($url)
{
    if (startsWith($url, 'http://')
        || startsWith($url, 'https://')
    ) return $url;

    $vocAliasToUrl = \alina\app::getConfig(['vocAliasUrl']);
    $url  = routeAccordance(ltrim($url, '/'), $vocAliasToUrl, false);

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
    return tag('a', $text, $configuration);
}