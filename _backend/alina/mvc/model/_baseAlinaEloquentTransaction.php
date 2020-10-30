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
        static::$keys[] = $transKey;
        if (static::$isInProgress) {
            return TRUE;
        }
        Dal::beginTransaction();
        static::$isInProgress = TRUE;

        return TRUE;
    }

    static public function commit($transKey = 'default')
    {
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
        Dal::rollback();
        //static::$keys         = [];
        static::$isInProgress = FALSE;
        static::$isSuccess    = FALSE;

        return TRUE;
    }
    #endregion Transaction.
}
