<?php

function fullClassName($appNamespace, $path, $className)
{
    $n = [
        trim($appNamespace, '\\'),
        trim($path, '\\'),
        trim($className, '\\'),
    ];
    array_filter($n);

    return '\\' . implode('\\', $n);
}

function shortClassName($className)
{
    $dirName = str_replace('\\', DIRECTORY_SEPARATOR, $className);

    return basename($dirName);
}

function returnClassMethod($class, $method, $params = [])
{
    if (!class_exists($class, TRUE))
        throw new \Exception("No Class: $class");

    $go = new $class();

    if (!method_exists($go, $method))
        throw new \Exception("No Method: $method");

    return call_user_func_array([$go, $method], $params);
}

/**
 * @see buildPathFromBlocks
 */
function buildClassNameFromBlocks()
{
    $args = func_get_args();
    $blocks  = [];
    foreach ($args as $block) {
        if (is_array($block)) {
            $blocks = array_merge($blocks, $block);
        } else {
            $blocks[] = $block;
        }
    }

    $NAMESPACE_SEPARATOR = '\\';
    foreach ($blocks as $i => $block) {
        $blocks[$i] = normalizePath($block);
        $blocks[$i] = trim($block, DIRECTORY_SEPARATOR);
        $blocks[$i] = str_replace(DIRECTORY_SEPARATOR, $NAMESPACE_SEPARATOR, $blocks[$i]);
    }

    $fullClassName = $NAMESPACE_SEPARATOR . implode($NAMESPACE_SEPARATOR, $blocks);

    return $fullClassName;
}
