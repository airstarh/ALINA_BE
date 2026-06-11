<?php
/**
 * According the official documentation,
 * @link https://github.com/illuminate/database
 * the initiation of Illuminate/database library should be performed only once.
 * So the Singleton is used below for such needs.
 */

namespace alina\vendorExtend\illuminate;

use \Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use \Illuminate\Events\Dispatcher;

// Laravel initiation
alinaLaravelCapsuleLoader::init();

class alinaLaravelCapsuleLoader
{

    static protected $objIlluminate = NULL;

    /**
     * Initiates PHP Illuminate Database toolkit.
     * @return Manager|false
     */
    static public function init()
    {

        // Make sure this function executes only once
        if (isset(static::$objIlluminate) && is_object(static::$objIlluminate)) {
            return static::$objIlluminate;
        }

        try {
            //DB Environment configs.
            $config = AlinaCfg('db');
            if (!is_array($config)) {
                $config = AlinaCfgDefault('db');
            }

            $capsule = new Manager;
            $capsule->addConnection($config);

            // Set the event dispatcher used by Eloquent models... (optional)
            $capsule->setEventDispatcher(new Dispatcher(new Container));

            // Make this Capsule instance available globally via static methods... (optional)
            $capsule->setAsGlobal();
            // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
            $capsule->bootEloquent();

            try {
                // Выполняем простой запрос: "SELECT 1"
                $result = $capsule->connection()->getPdo()->query('SELECT 1')->fetch();

                if ($result) {
                    static::$objIlluminate = $capsule;
                    return static::$objIlluminate;
                }
            } catch (\Throwable $e) {
                return false;
            }

            return false;

        } catch (\Throwable $e) {
            return false;
        }
    }
}
