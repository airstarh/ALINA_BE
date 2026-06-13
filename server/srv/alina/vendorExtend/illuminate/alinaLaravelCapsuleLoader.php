<?php

/**
 * According the official documentation,
 * @link https://github.com/illuminate/database
 * the initiation of Illuminate/database library should be performed only once.
 * So the Singleton is used below for such needs.
 */

namespace alina\vendorExtend\illuminate;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

class alinaLaravelCapsuleLoader
{
    protected static $objIlluminate = null;

    /**
     * Initiates PHP Illuminate Database toolkit.
     * @return Manager|false
     */
    public static function init()
    {
        $res = false;

        if (isset(static::$objIlluminate) && is_object(static::$objIlluminate)) {
            $res = true;

            return static::$objIlluminate;
        }

        try {
            $config = AlinaCfg('db');

            if (! is_array($config)) {
                $config = AlinaCfgDefault('db');
            }

            $capsule = new Manager();
            $capsule->addConnection($config);

            $capsule->setEventDispatcher(new Dispatcher(new Container()));

            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            try {
                $result = $capsule->connection()->getPdo()->query('SELECT 1')->fetch();

                if ($result) {
                    $res                   = true;
                    static::$objIlluminate = $capsule;

                    return static::$objIlluminate;
                }
            }
            catch (\Throwable $e) {
                $res = false;
            }
        }
        catch (\Throwable $e) {
            $res = false;
        }
        exit('No db');
    }
}
