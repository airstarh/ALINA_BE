<?php

declare(strict_types=1);

$tests = [];

function test(string $name, callable $callback): void
{
    global $tests;
    $tests[$name] = $callback;
}

function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $detail = $message !== '' ? $message . ': ' : '';
        throw new RuntimeException(
            $detail . 'expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true)
        );
    }
}

function assertTrueValue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message !== '' ? $message : 'condition is false');
    }
}

function assertThrows(callable $callback, string $exceptionClass): Throwable
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if (!$exception instanceof $exceptionClass) {
            throw new RuntimeException(
                'expected ' . $exceptionClass . ', got ' . $exception::class,
                previous: $exception
            );
        }

        return $exception;
    }

    throw new RuntimeException('expected ' . $exceptionClass . ' to be thrown');
}

$requested = array_slice($argv, 1);
$files = $requested === []
    ? glob(__DIR__ . '/*.test.php') ?: []
    : array_map(
        static fn (string $file): string => str_contains($file, '/')
            ? $file
            : __DIR__ . '/' . $file,
        $requested
    );

sort($files);

foreach ($files as $file) {
    require $file;
}

$failures = 0;
foreach ($tests as $name => $callback) {
    try {
        $callback();
        fwrite(STDOUT, "PASS {$name}\n");
    } catch (Throwable $exception) {
        $failures++;
        fwrite(STDERR, "FAIL {$name}: {$exception->getMessage()}\n");
    }
}

$total = count($tests);
fwrite(STDOUT, sprintf("%d tests, %d failures\n", $total, $failures));
exit($failures === 0 ? 0 : 1);
