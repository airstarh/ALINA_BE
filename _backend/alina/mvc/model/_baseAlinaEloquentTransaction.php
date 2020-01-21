<?php

namespace alina\mvc\model;

use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use \Illuminate\Database\Capsule\Manager as Dal;

// Laravel initiation
Loader::init();

class _baseAlinaEloquentTransaction
{
    //Make this class non-extendable and non-instanciatable.
    //Static methods only.
    static public $keys = [];

    #region Transaction.
    static public $isInProgress = FALSE;
    static public $isSuccess    = NULL;

    private function __construct() { }

    static public function begin($transKey = 'default')
    {
        error_log(__FUNCTION__, 0);

        static::$keys[] = $transKey;

        error_log(json_encode(static::$keys), 0);

        if (static::$isInProgress) {
            return TRUE;
        }

        Dal::beginTransaction();
        static::$isInProgress = TRUE;

        return TRUE;
    }

    static public function commit($transKey = 'default')
    {
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
                static::$isInProgress = FALSE;
                static::$isSuccess    = TRUE;
                error_log('--- DB COMMIT SUCCESS ---', 0);
            }

            return TRUE;
        } //ToDO: Perhaps, this try-catch is redundant...
        catch (\Exception $e) {
            static::rollback();
            throw $e;
        }
    }

    static public function rollback()
    {
        error_log(__FUNCTION__, 0);
        error_log(json_encode(static::$keys), 0);

        Dal::rollback();
        //static::$keys         = [];
        static::$isInProgress = FALSE;
        static::$isSuccess    = FALSE;

        return TRUE;
    }
    #endregion Transaction.
}
