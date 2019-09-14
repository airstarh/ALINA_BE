<?php

/**
 * Checks if a $subject could be passed to foreach.
 * @param mixed $subject
 * @return bool
 */
function isIterable($subject)
{
    return (is_array($subject) || is_object($subject));
}

function toArray($v)
{
    if (is_array($v)) {
        return $v;
    }

    if (isIterable($v)) {
        // ToDo: Make less heavy
        $array = json_decode(json_encode($v), TRUE);
    } else {
        $array = [$v];
    }

    return $array;
}

/**
 * @param $v
 * @return mixed
 * @throws Exception
 */
function toObject($v)
{
    if (!isset($v) || empty($v)) {
        return new \stdClass();
    }

    if (is_object($v)) {
        return $v;
    }

    if (is_array($v)) {
        // ToDo: Make less heavy
        return json_decode(json_encode($v), FALSE);
    }

    if (is_string($v)) {
        $res = json_decode($v);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $res;
        }
    }

    throw new \Exception('Alina cannot convert to object: ' . var_export($v, 1));
    //return $object;
}

//@link https://stackoverflow.com/a/6041773/3142281
function isStringValidJson($string)
{
    json_decode($string);

    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Transforms input data to 'ASC' or 'DESC' string.
 * @param string|int|boolean $dir
 * @return string 'ASC' or 'DESC'
 */
function getSqlDirection($dir)
{
    if (is_string($dir)) {
        $dir = trim(strtoupper($dir));
        if ($dir === 'ASC' || $dir === 'DESC') {
            return $dir;
        }
    }

    $dir = filter_var($dir, FILTER_VALIDATE_BOOLEAN)
        ? 'ASC'
        : 'DESC';

    return $dir;
}

function utf8ize($d)
{
    if (is_array($d) || is_object($d)) {
        foreach ($d as &$v) {
            $v = utf8ize($v);
        }
    } else {
        $enc   = mb_detect_encoding($d);
        $value = iconv($enc, 'UTF-8', $d);

        return $value;
    }

    return $d;
}

//ToDo: Less heavy. Validate input.
function mergeSimpleObjects(...$objects)
{
    $res = new stdClass();
    foreach ($objects as $o) {
        $res = (object)array_merge((array)$res, (array)$o);
    }

    return $res;
}

/**
 * Designed to completely remove WordPress problem
 * https://stackoverflow.com/questions/3148712/regex-code-to-fix-corrupt-serialized-php-data/55074706#55074706
 * @param string $str
 * @return bool|array
 */
function hlpSuperUnSerialize($str)
{
    //ToDo: see later: https://stackoverflow.com/a/38708463/3142281

    #region Simple Security
    if (
        empty($str)
        || !is_string($str)
        || !preg_match('/^[aOs]:/', $str)
    ) {
        return FALSE;
    }
    #endregion Simple Security

    #region SOLUTION 0
    // PHP default :-)
    $repSolNum = 0;
    $strFixed  = $str;
    $arr       = @unserialize($strFixed);
    if (FALSE !== $arr) {
        alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

        return $arr;
    }
    #endregion SOLUTION 0

    #region SOLUTION 1
    // @link https://stackoverflow.com/a/5581004/3142281
    $repSolNum = 1;
    $strFixed  = preg_replace_callback(
        '/s:([0-9]+):\"(.*?)\";/',
        function ($matches) { return "s:" . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
        $str
    );
    $arr       = @unserialize($strFixed);
    if (FALSE !== $arr) {
        alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

        return $arr;
    }
    #endregion SOLUTION 1

    #region SOLUTION 2
    // @link https://stackoverflow.com/a/24995701/3142281
    $repSolNum = 2;
    $strFixed  = preg_replace_callback(
        '/s:([0-9]+):\"(.*?)\";/',
        function ($match) {
            return "s:" . strlen($match[2]) . ':"' . $match[2] . '";';
        },
        $str);
    $arr       = @unserialize($strFixed);
    if (FALSE !== $arr) {
        alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

        return $arr;
    }
    #endregion SOLUTION 2

    #region SOLUTION 3
    // @link https://stackoverflow.com/a/34224433/3142281
    $repSolNum = 3;
    // securities
    $strFixed = preg_replace("%\n%", "", $str);
    // doublequote exploding
    $data     = preg_replace('%";%', "µµµ", $strFixed);
    $tab      = explode("µµµ", $data);
    $new_data = '';
    foreach ($tab as $line) {
        $new_data .= preg_replace_callback(
            '%\bs:(\d+):"(.*)%',
            function ($matches) {
                $string       = $matches[2];
                $right_length = strlen($string); // yes, strlen even for UTF-8 characters, PHP wants the mem size, not the char count

                return 's:' . $right_length . ':"' . $string . '";';
            },
            $line);
    }
    $strFixed = $new_data;
    $arr      = @unserialize($strFixed);
    if (FALSE !== $arr) {
        alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

        return $arr;
    }
    #endregion SOLUTION 3

    #region SOLUTION 4
    // @link https://stackoverflow.com/a/36454402/3142281
    $repSolNum = 4;
    $strFixed  = preg_replace_callback(
        '/s:([0-9]+):"(.*?)";/',
        function ($match) {
            return "s:" . strlen($match[2]) . ":\"" . $match[2] . "\";";
        },
        $str
    );
    $arr       = @unserialize($strFixed);
    if (FALSE !== $arr) {
        alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

        return $arr;
    }
    #endregion SOLUTION 4

    #region SOLUTION 5
    // @link https://stackoverflow.com/a/38890855/3142281
    $repSolNum = 5;
    $strFixed  = preg_replace_callback('/s\:(\d+)\:\"(.*?)\";/s', function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; }, $str);
    $arr       = @unserialize($strFixed);
    if (FALSE !== $arr) {
        alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

        return $arr;
    }
    #endregion SOLUTION 5

    #region SOLUTION 6
    // @link https://stackoverflow.com/a/38891026/3142281
    $repSolNum = 6;
    $strFixed  = preg_replace_callback(
        '/s\:(\d+)\:\"(.*?)\";/s',
        function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
        $str);;
    $arr = @unserialize($strFixed);
    if (FALSE !== $arr) {
        alinaErrorLog("UNSERIALIZED!!! SOLUTION {$repSolNum} worked!!!");

        return $arr;
    }
    #endregion SOLUTION 6

    alinaErrorLog('Completely unable to deserialize.');

    return FALSE;
}

function hlpGetBeautifulJsonString($d)
{
    $s = $d;
    if (is_string($d)) {
        if (isStringValidJson($d)) {
            $s = json_decode($d);
        } else {
            return $s;
        }
    }
    $s = json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    return $s;
}

/**
 * @link https://stackoverflow.com/a/31691249/3142281
 * @param array $parsed
 * @return string
 */
function hlpUnParseUri(array $parsed)
{
    $get = function ($key) use ($parsed) {
        return isset($parsed[$key]) ? $parsed[$key] : NULL;
    };

    $pass      = $get('pass');
    $user      = $get('user');
    $userinfo  = (!empty($pass)) ? "$user:$pass" : $user;
    $port      = $get('port');
    $scheme    = $get('scheme');
    $query     = $get('query');
    $fragment  = $get('fragment');
    $authority =
        (!empty($userinfo) ? "$userinfo@" : '') .
        $get('host') .
        ($port ? ":$port" : '');

    return
        (strlen($scheme) ? "$scheme:" : '') .
        (strlen($authority) ? "//$authority" : '') .
        $get('path') .
        (strlen($query) ? "?$query" : '') .
        (strlen($fragment) ? "#$fragment" : '');
}
