<?php

class BorgTestData
{
    ##################################################
    #region BASIS
    static public function dataTypes()
    {
        return [
            '_null'              => null,
            '_sting_null'        => 'null',
            '_sting_empty'       => '',
            '_number_0'          => 0,
            '_string_0'          => '0',
            '_bool_false'        => false,
            '_string_false'      => 'false',
            '_bool_true'         => true,
            '_string_string'     => 'some string',
            '_string_valid_json' => '{"key":"value"}',
            '_number_101'        => 101,
            '_string_101'        => '101',
            //'_object'            => (object)['oField' => 'oValue',],
        ];
    }
    #endregion BASIS
    ##################################################
    #region RANDOMIZER

    static public function randLetter()
    {
        $range = array_merge(range('A', 'Z'), range('a', 'z'));
        $index = array_rand($range, 1);
        return $range[$index];
    }

    static public function randString($length = 32)
    {
        $res     = [];
        $counter = 1;
        while ($counter <= $length) {
            $res[] = static::randLetter();
            ++$counter;
        }
        return implode('', $res);

    }

    static public function randStringLength($min = 1, $max = 33)
    {
        $length = mt_rand($min, $max);
        return static::randString($length);
    }

    static public function randMail()
    {
        $name    = static::randStringLength(3, 10);
        $domain2 = static::randStringLength(2, 10);
        $domain1 = static::randStringLength(2, 2);
        return mb_strtolower("$name@$domain2.$domain1");
    }

    static public function randProperName($min = 3, $max = 12)
    {
        $str = static::randStringLength($min, $max);
        $str = mb_strtolower($str);
        $str = ucfirst($str);
        return $str;
    }

    static public function randInteger($min = 0, $max = 100)
    {
        return rand($min, $max);
    }

    static public function randFloat($min = 0, $max = 100, $fMin = 0, $fMax = 9999)
    {
        $int      = mt_rand($min, $max);
        $fraction = mt_rand($fMin, $fMax);
        $length   = strlen((string)$fMax);
        $degree   = 10 ** $length;
        return $int + $fraction / $degree;
    }

    static public function randPhone()
    {
        $time = time();
        return "+7$time";
    }

    static public function randFormData()
    {
        $rand = time();
        return [
            'firstname' => static::randStringLength(3, 12),
            'lastname'  => static::randStringLength(3, 12),
            'mail'      => static::randMail(),
            'password'  => 'qQ!@123qQ!@123qQ!@123',
            'phone'     => static::randPhone(),
            'nickname'  => static::randStringLength(3, 12),
        ];
    }
    #endregion RANDOMIZER
    ##################################################

    #####
}