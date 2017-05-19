<?php

function isIterable($subject)
{
    return (is_array($subject) || is_object($subject));
}

function toArray($v)
{
    if (is_array($v))
        return $v;

    if (isIterable($v)) {
        // ToDo: Make less heavy
        $array = json_decode(json_encode($v), true);
    }
    else {
        $array = [$v];
    }
    return $array;

}

function toObject($v)
{
    if (is_object($v))
        return $v;

    if (isIterable($v)) {
        // ToDo: Make less heavy
        $object = json_decode(json_encode($v), false);
    }
    else {
        throw new \Exception('Unconvertable value');
    }
    return $object;
}
