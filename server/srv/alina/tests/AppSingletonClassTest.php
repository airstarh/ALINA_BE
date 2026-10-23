<?php

declare(strict_types=1);

require_once __DIR__ . '/../App.php';

use alina\App;

$reflection = new ReflectionClass(App::class);
$instance = $reflection->newInstanceWithoutConstructor();
$instanceProperty = $reflection->getProperty('instance');
$instanceProperty->setValue(null, $instance);

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $actual = App::get();
}
finally {
    restore_error_handler();
}

if ($actual !== $instance) {
    fwrite(STDERR, "FAIL: App::get() must return the initialized application singleton\n");
    exit(1);
}

echo "PASS: application singleton class check\n";
