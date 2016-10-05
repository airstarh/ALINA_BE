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