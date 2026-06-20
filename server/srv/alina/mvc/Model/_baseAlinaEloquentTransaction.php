<?php

namespace alina\mvc\Model;

use alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use Exception;
use Illuminate\Database\Capsule\Manager as Dal;

// Laravel initiation
Loader::init();

class _baseAlinaEloquentTransaction
{
    //Make this class non-extendable and non-instanciatable.
    //Static methods only.
    public static $keys = [];
    #region Transaction.
    public static $isInProgress = false;
    public static $isSuccess    = null;

    private function __construct()
    {
    }

    public static function begin($transKey = 'default')
    {
        static::$keys[] = $transKey;

        if (static::$isInProgress) {
            return true;
        }
        Dal::beginTransaction();
        static::$isInProgress = true;

        return true;
    }

    public static function commit($transKey = 'default')
    {
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
            }

            return true;
        } //ToDO: Perhaps, this try-catch is redundant...
        catch (Exception $e) {
            static::rollback();

            throw $e;
        }
    }

    public static function rollback()
    {
        Dal::rollback();
        //static::$keys         = [];
        static::$isInProgress = false;
        static::$isSuccess    = false;

        return true;
    }
    #endregion Transaction.
}
