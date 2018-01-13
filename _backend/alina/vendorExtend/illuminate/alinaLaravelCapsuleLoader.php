<?php
/**
 * According the official documentation,
 * @link https://github.com/illuminate/database
 * the initiation of Illuminate/database library should be performed only once.
 * So the Singleton is used below for such needs.
 */

namespace alina\vendorExtend\illuminate;

use \Illuminate\Container\Container;
use \Illuminate\Events\Dispatcher;

// Laravel initiation
alinaLaravelCapsuleLoader::init();

/**
 * Some features are absent in the parent class.
 */
class alinaLaravelCapsuleLoader {

	protected function __construct() { }

	static protected $objIlluminate = null;

	/**
	 * Initiates PHP Illuminate Database toolkit.
	 * @return \Illuminate\Database\Capsule\Manager object
	 */
	static public function init() {

		// Make sure this function executes only once
		if (isset(static::$objIlluminate) && is_object(static::$objIlluminate)) {
			return static::$objIlluminate;
		}

		//region DM Environment configs.
		$config = \alina\app::getConfig('db');

		$capsule = new alinaLaravelCapsule;
		$capsule->addConnection($config);

		// Set the event dispatcher used by Eloquent models... (optional)
		$capsule->setEventDispatcher(new Dispatcher(new Container));

		// Make this Capsule instance available globally via static methods... (optional)
		$capsule->setAsGlobal();
		// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
		$capsule->bootEloquent();
		static::$objIlluminate = $capsule;
		return static::$objIlluminate;
	}
}