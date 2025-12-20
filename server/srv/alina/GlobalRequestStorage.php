<?php

namespace alina;

use alina\traits\Singleton;
use alina\Utils\Arr;

class GlobalRequestStorage
{
    #region Singleton
    use Singleton;

    protected function __construct() { }
    #endregion Singleton
    #region CRUD
    protected $memory = [
        'alina_response_success' => 1,
        'BaseModelQueries'       => 0,
        'modelMetaInfo'          => [],
        #####
        'pageTitle'              => NULL,
        'pageDescription'        => NULL,
        'viewData'               => NULL,
        'tagRelAlternateUrl'     => NULL,
        'tagRelCanonicalUrl'     => NULL,
        #####
        /**In sub-props*/
        /*
        'pageCurrentNumber'      => 1,
        'pageSize'               => -1,
        'rowsTotal'              => 0,
        'pagesTotal'             => 0,
        */
        #####
    ];

    static public function set($prop, $val)
    {
        Arr::setArrayValue($prop, $val, static::obj()->memory);

        return $val;
    }

    static public function setPlus1($prop)
    {
        $count = static::get($prop);
        if (empty($count)) {
            $count = 0;
        }
        $count++;
        static::set($prop, $count);

        return $count;
    }

    static public function get($prop)
    {
        if (Arr::arrayHasPath($prop, static::obj()->memory)) {
            return Arr::getArrayValue($prop, static::obj()->memory);
        }

        return NULL;
    }

    static public function getAll()
    {
        return static::obj()->memory;
    }
    #endregion CRUD
}
