<?php

declare(strict_types=1);

namespace Illuminate\Container {
    final class Container
    {
    }
}

namespace Illuminate\Events {
    final class Dispatcher
    {
        public function __construct(object $container)
        {
        }
    }
}

namespace Illuminate\Database\Capsule {
    use RuntimeException;

    final class FakeResult
    {
        public function fetch(): array
        {
            return [1];
        }
    }

    final class FakePdo
    {
        public function query(string $query): FakeResult
        {
            Manager::$attempts++;

            if (Manager::$attempts <= Manager::$failuresBeforeSuccess) {
                throw new RuntimeException('simulated connection failure');
            }

            return new FakeResult();
        }
    }

    final class FakeConnection
    {
        public function getPdo(): FakePdo
        {
            return new FakePdo();
        }
    }

    final class Manager
    {
        public static int $attempts              = 0;
        public static int $failuresBeforeSuccess = 0;

        public function addConnection(array $config): void
        {
        }

        public function setEventDispatcher(object $dispatcher): void
        {
        }

        public function setAsGlobal(): void
        {
        }

        public function bootEloquent(): void
        {
        }

        public function connection(): FakeConnection
        {
            return new FakeConnection();
        }
    }
}

namespace alina\vendorExtend\illuminate {
    function AlinaCfg(string $path): array
    {
        if ($path === 'dbRetry') {
            return [
                'timeoutSeconds'  => (int) ($GLOBALS['retryTimeoutSeconds'] ?? 0),
                'intervalSeconds' => 0,
            ];
        }

        return [];
    }

    function AlinaCfgDefault(string $path): array
    {
        return AlinaCfg($path);
    }
}

namespace {
    use Illuminate\Database\Capsule\Manager;

    const CHILD_ARGUMENT = '--child';

    if (($argv[1] ?? '') === CHILD_ARGUMENT) {
        $GLOBALS['retryTimeoutSeconds'] = (int) ($argv[2] ?? 0);
        Manager::$failuresBeforeSuccess = (int) ($argv[3] ?? 0);

        require __DIR__ . '/../vendorExtend/illuminate/alinaLaravelCapsuleLoader.php';

        alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader::init();
        fwrite(STDOUT, 'attempts=' . Manager::$attempts . PHP_EOL);
        exit(0);
    }

    function runChild(int $timeoutSeconds, int $failuresBeforeSuccess): array
    {
        $command = sprintf(
            '%s %s %s %d %d',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__FILE__),
            CHILD_ARGUMENT,
            $timeoutSeconds,
            $failuresBeforeSuccess,
        );

        $output = [];
        exec($command . ' 2>&1', $output, $exitCode);

        return [$exitCode, implode(PHP_EOL, $output)];
    }

    function assertSameValue(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            fwrite(
                STDERR,
                sprintf("FAIL: %s\nExpected: %s\nActual: %s\n", $message, var_export($expected, true), var_export($actual, true)),
            );
            exit(1);
        }
    }

    function assertContainsText(string $needle, string $haystack, string $message): void
    {
        if (! str_contains($haystack, $needle)) {
            fwrite(STDERR, sprintf("FAIL: %s\nMissing: %s\nOutput: %s\n", $message, $needle, $haystack));
            exit(1);
        }
    }

    [$successCode, $successOutput] = runChild(1, 2);
    assertSameValue(0, $successCode, 'a later successful connection must return success');
    assertContainsText('Database unavailable (attempt 1); retrying', $successOutput, 'CLI callers must see retry progress');
    assertContainsText('attempts=3', $successOutput, 'the loader must retry until the connection succeeds');

    [$failureCode, $failureOutput] = runChild(0, 1);
    assertSameValue(69, $failureCode, 'an exhausted CLI connection must return EX_UNAVAILABLE');
    assertContainsText('Database unavailable after 0 seconds (1 attempt)', $failureOutput, 'the terminal CLI error must explain the retry result');

    fwrite(STDOUT, "PASS: capsule database retry behavior\n");
}
