<?php

declare(strict_types=1);

require_once __DIR__ . '/../Utils/Arr.php';

use alina\Utils\Arr;

function arrFirstExpectSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$values = ['alpha' => 11, 'beta' => 22];

arrFirstExpectSame('alpha', Arr::firstArrayKey($values), 'firstArrayKey must return the first key');
arrFirstExpectSame(11, Arr::firstArrayValue($values), 'firstArrayValue must return the first value');
arrFirstExpectSame(null, Arr::firstArrayKey([]), 'firstArrayKey must return null for an empty array');
arrFirstExpectSame(null, Arr::firstArrayValue([]), 'firstArrayValue must return null for an empty array');

echo "PASS: first-array helpers\n";
