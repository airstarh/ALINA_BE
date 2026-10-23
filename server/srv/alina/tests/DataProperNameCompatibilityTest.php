<?php

declare(strict_types=1);

require_once __DIR__ . '/../Utils/Data.php';

use alina\Utils\Data;

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $actual = Data::filterVarStrProperName('<b>O\'Reilly "quoted" & Jane</b>');
}
finally {
    restore_error_handler();
}

$expected = 'O&#39;Reilly &#34;quoted&#34; & Jane';

if ($actual !== $expected) {
    fwrite(STDERR, sprintf("FAIL: proper-name filtering changed\nExpected: %s\nActual: %s\n", $expected, $actual));
    exit(1);
}

fwrite(STDOUT, "PASS: proper-name filtering without PHP deprecations\n");
