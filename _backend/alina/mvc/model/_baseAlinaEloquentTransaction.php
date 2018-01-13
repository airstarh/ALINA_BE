<?php

namespace alina\mvc\model;

use \alina\vendorExtend\illuminate\alinaLaravelCapsule as Dal;

class _baseAlinaEloquentTransaction {

	//Make this class non-extendable and non-instanciatable.
	//Static methods only.
	private function __construct() { }

	#region Transaction.
	static public $keys = [];

	static public $isInProgress = false;

	static public $isSuccess = null;

	static public function begin($transKey = 'default') {
		error_log(__FUNCTION__, 0);

		static::$keys[] = $transKey;

		error_log(json_encode(static::$keys), 0);

		if (static::$isInProgress) {
			return true;
		}

		Dal::beginTransaction();
		static::$isInProgress = true;
		return true;
	}

	static public function rollback() {
		error_log(__FUNCTION__, 0);
		error_log(json_encode(static::$keys), 0);

		Dal::rollback();
		//static::$keys         = [];
		static::$isInProgress = false;
		static::$isSuccess    = false;

		return true;
	}

	static public function commit($transKey = 'default') {
		error_log(__FUNCTION__, 0);
		error_log(json_encode(static::$keys), 0);
		try {
			$lastStartedTransaction = array_slice(static::$keys, -1)[0];
			if ($transKey === $lastStartedTransaction) {
				array_pop(static::$keys);
			}
			if (count(static::$keys) === 0) {
				Dal::commit();
				static::$keys         = [];
				static::$isInProgress = false;
				static::$isSuccess    = true;
				error_log('--- DB COMMIT SUCCESS ---', 0);
			}

			return true;
		} //ToDO: Perhaps, this try-catch is redundant...
		catch (\Exception $e) {
			static::rollback();
			throw $e;
		}
	}
	#endregion Transaction.
}