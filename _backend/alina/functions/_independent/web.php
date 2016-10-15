<?php

function ref($url)
{
    if (startsWith($url, 'http://')
        || startsWith($url, 'https://')
    )
        return $url;
    $url = ltrim($url, '/');

    return "//{$_SERVER['HTTP_HOST']}/{$url}";
}

function l($ref, $text = '', $configuration = [])
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

    $href .= ref($ref) . $get . $hash;

    $configuration['href'] = $href;

    return tag('a', $text, $configuration);
}

function tag($tagName, $text = '', $configuration = [])
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

    $attributes = convertAttributesArrayToString($configuration);

    return "<$tagName $attributes>$additionalContentBefore $text</$tagName>";
}

function convertAttributesArrayToString($attributes)
{
    $attributeString = '';
    foreach ($attributes as $attribute => $value) {
        if (is_array($value))
            $value = implode(' ', $value);
        $attributeString .= " $attribute='$value'";
    }

    return $attributeString;
}

function wrapToDiv($content)
{
    return tag('div', $content, ['class' => ['wrapped-item']]);
}

function redirect($page, $code = 301)
{
    if (startsWith($page, 'http://')
        || startsWith($page, 'https://')
    ) {
        header("Location: $page", TRUE, $code);
        die();
    }

    $page = ref($page);
    header("Location: $page", TRUE, $code);
    die();
}

#region URL's, Aliases, Routes
function routeAccordance($url, array $vocabulary = [], $aliasToSystemRoute = TRUE)
{
    foreach ($vocabulary as $aliasMask => $urlMask) {

        $compareWith       = ($aliasToSystemRoute) ? $aliasMask : $urlMask;
        $regularExpression = routeRegExp($compareWith);

        if (preg_match($regularExpression, $url)) {

            if ($aliasToSystemRoute) {
                return aliasToUrl($aliasMask, $url, $urlMask);
            }
            else {
                return urlToAlias($urlMask, $url, $aliasMask);
            }
        }
    }

    return $url;
}

function routeRegExp($string)
{
    $parts = explode('/', $string);

    $regularExpression = [];
    foreach ($parts as $v) {
        if ($v === ':p' || FALSE !== strpos($v, ':p')) {
            $regularExpression[] = '.+?';
        }
        else {
            $regularExpression[] = $v;
        }
    }
    $regularExpression = implode('\/', $regularExpression);
    $regularExpression = '/^' . $regularExpression . '$/i';

    return $regularExpression;
}

function aliasToUrl($aliasMask, $systemRoute, $systemRouteMask)
{
    return routeConverter(
        $aliasMask,
        $systemRoute,
        $systemRouteMask
    );
}

function urlToAlias($systemRouteMask, $systemRoute, $aliasMask)
{
    return routeConverter(
        $systemRouteMask,
        $systemRoute,
        $aliasMask
    );
}

function routeConverter($fromMask, $source, $toMask)
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
        }
        else {
            $convertedResult[] = $pN;
        }
    }

    return implode('/', $convertedResult);
}

#endregion URL's, Aliases, Routes

function template($fileFullPath, $data = NULL)
{
    ob_start();
    ob_implicit_flush(FALSE);
    require($fileFullPath);
    $output = ob_get_clean();

    return $output;
}

function isAjax()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return TRUE;
    }
    if (isset($_GET['isAjax']) && !empty($_GET['isAjax'])) {
        return TRUE;
    }
    if (isset($_POST['isAjax']) && !empty($_POST['isAjax'])) {
        return TRUE;
    }

    return FALSE;
}