<?php

namespace alina\Utils;

class Str
{
    public static function startsWith($haystack, $needle)
    {
        $length = mb_strlen($needle);

        return (mb_substr($haystack, 0, $length) === (string)$needle);
    }

    public static function endsWith($haystack, $needle)
    {
        $length = mb_strlen($needle);

        if ($length == 0) {
            return true;
        }

        return (mb_substr($haystack, -$length) === (string)$needle);
    }

    public static function ifContains($haystack, $needle)
    {
        return mb_stripos($haystack, $needle) !== false;
    }

    public static function removeEnters($haystack, $needle = '')
    {
        return $string = str_replace(["\n", "\r"], $needle, $haystack);
    }

    public static function lessThan($str, $number)
    {
        return mb_strlen($str) < $number;
    }

    public static function anyToString($data): string
    {
        if ($data === null) {
            return 'null';
        }

        if (is_bool($data)) {
            return $data ? 'true' : 'false';
        }

        if (is_array($data)) {
            return \alina\Utils\Data::hlpGetBeautifulJsonString($data);
        }

        if (is_object($data)) {
            return \alina\Utils\Data::hlpGetBeautifulJsonString($data);
        }

        if (is_resource($data)) {
            return 'resource(' . get_resource_type($data) . ')';
        }

        return (string) $data;
    }
}
