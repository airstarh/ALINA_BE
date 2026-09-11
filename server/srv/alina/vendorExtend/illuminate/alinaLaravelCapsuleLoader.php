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
use RuntimeException;
use Throwable;

class alinaLaravelCapsuleLoader
{
    protected static $objIlluminate = null;

    /**
     * Initiates PHP Illuminate Database toolkit.
     * @return Manager|false
     */
    public static function init()
    {
        if (isset(static::$objIlluminate) && is_object(static::$objIlluminate)) {
            return static::$objIlluminate;
        }

        $config = AlinaCfg('db');

        if (! is_array($config)) {
            $config = AlinaCfgDefault('db');
        }

        $retryConfig = AlinaCfg('dbRetry');

        if (! is_array($retryConfig)) {
            $retryConfig = AlinaCfgDefault('dbRetry');
        }

        $timeoutSeconds  = max(0, (int) ($retryConfig['timeoutSeconds'] ?? 0));
        $intervalSeconds = max(0, (int) ($retryConfig['intervalSeconds'] ?? 10));
        $startedAt       = hrtime(true);
        $attempts        = 0;
        $lastError       = null;

        do {
            $attempts++;

            try {
                $capsule = new Manager();
                $capsule->addConnection($config);
                $capsule->setEventDispatcher(new Dispatcher(new Container()));
                $capsule->setAsGlobal();
                $capsule->bootEloquent();

                $result = $capsule->connection()->getPdo()->query('SELECT 1')->fetch();

                if ($result) {
                    static::$objIlluminate = $capsule;

                    return static::$objIlluminate;
                }
            }
            catch (Throwable $e) {
                $lastError = $e;
            }

            $elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;

            if ($elapsedSeconds >= $timeoutSeconds) {
                break;
            }

            $sleepSeconds = min($intervalSeconds, (int) ceil($timeoutSeconds - $elapsedSeconds));

            if (PHP_SAPI === 'cli') {
                fwrite(
                    STDERR,
                    sprintf(
                        'Database unavailable (attempt %d); retrying in %d seconds...' . PHP_EOL,
                        $attempts,
                        $sleepSeconds,
                    ),
                );
            }

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }
        while (true);

        $message = sprintf(
            'Database unavailable after %d seconds (%d %s)',
            $timeoutSeconds,
            $attempts,
            $attempts === 1 ? 'attempt' : 'attempts',
        );

        if ($lastError) {
            $message .= ': ' . $lastError->getMessage();
        }

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . PHP_EOL);
            exit(69);
        }

        throw new RuntimeException($message, 0, $lastError);
    }
}
