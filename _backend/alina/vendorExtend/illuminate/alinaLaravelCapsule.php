<?php
/**
 * Created by PhpStorm.
 * User: ladmin
 * Date: 17.05.2017
 * Time: 0:18
 */

namespace alina\vendorExtend\illuminate;

/**
 * Some features are absent in the parent class.
 */
class alinaLaravelCapsule extends \Illuminate\Database\Capsule\Manager
{
// Adds ability to use raw SQL
    public static function raw($value)
    {
        return static::$instance->connection()->raw($value);
    }

    // Adds ability for easy selects
    public static function select($query, $bindings = [], $useReadPdo = TRUE, $connection = NULL)
    {
        return static::$instance->connection($connection)->select($query, $bindings, $useReadPdo);
    }

    public static function selectOne($query, $bindings = [], $connection = NULL)
    {
        return static::$instance->connection($connection)->selectOne($query, $bindings);
    }
}