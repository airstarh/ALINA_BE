<?php

namespace alina\Utils;

use alina\Message;
use alina\mvc\Model\router_alias;

class Html
{
    static public function tag($tagName, $text = '', $configuration = [])
    {
        $additionalContentBefore = '';
        // For fieldset
        if ($tagName == 'fieldset') {
            if (isset($configuration['legend'])) {
                if (!empty($configuration['legend'])) {
                    $additionalContentBefore .= "<legend>{$configuration['legend']}</legend>";
                }
                unset($configuration['legend']);
            }
        }
        $attributes = static::convertAttributesArrayToString($configuration);

        return "<$tagName $attributes>$additionalContentBefore $text</$tagName>";
    }

    static public function ref($url)
    {
        //ToDO: Doubtful: http is enough... Unless a web-site is like httpdocs.com...
        if (\alina\Utils\Str::startsWith($url, 'http://') || \alina\Utils\Str::startsWith($url, 'https://')) {
            return $url;
        }
        $url = ltrim($url, '/');

        return "//{$_SERVER['HTTP_HOST']}/{$url}";
    }

    static public function l($ref, $text = '', $configuration = [])
    {
        $href     = '';
        $get      = '';
        $getArray = [];
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
        $href                  .= static::ref($ref) . $get . $hash;
        $configuration['href'] = $href;

        return static::tag('a', $text, $configuration);
    }

    static public function convertAttributesArrayToString($attributes)
    {
        $attributeString = '';
        foreach ($attributes as $attribute => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $attributeString .= " $attribute='$value'";
        }

        return $attributeString;
    }

    static public function wrapToDiv($content)
    {
        return static::tag('div', $content, ['class' => ['wrapped-item']]);
    }
    ##################################################
    #region DEPENDENT
    static public function aRef($url)
    {
        if (\alina\Utils\Str::startsWith($url, 'http://')
            ||
            \alina\Utils\Str::startsWith($url, 'https://')
        ) {
            return $url;
        }
        $vocAliasToUrl = AlinaCfg(['vocAliasUrl']);
        $bdVoc         = (new router_alias())->getAsVoc();
        $vocAliasToUrl = array_merge($vocAliasToUrl, $bdVoc);
        $url           = \alina\Utils\Url::routeAccordance($url, $vocAliasToUrl, FALSE);
        $url           = ltrim($url, '/');
        $url           = ltrim($url, '\\');

        return "//{$_SERVER['HTTP_HOST']}/{$url}";
    }

    static public function aL($ref, $text = '', $configuration = [])
    {
        $href     = '';
        $get      = '';
        $getArray = [];
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
        $href                  .= static::aRef($ref) . $get . $hash;
        $configuration['href'] = $href;

        return \alina\Utils\Html::tag('a', $text, $configuration);
    }
    #endregion DEPENDENT
    ##################################################
}
