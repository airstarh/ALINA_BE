<?php

declare(strict_types=1);

require_once __DIR__ . '/../mvc/Model/_BaseAlinaModel.php';
require_once __DIR__ . '/../mvc/Model/file.php';

use alina\mvc\Model\file;

function fileSizeExpectSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$path = tempnam(sys_get_temp_dir(), 'alina-file-size-');

if ($path === false) {
    fwrite(STDERR, "FAIL: could not create temporary file\n");
    exit(1);
}

try {
    file_put_contents($path, str_repeat('a', 1536));

    $model = (new ReflectionClass(file::class))->newInstanceWithoutConstructor();

    $missingSize = ['size' => null, 'dir' => $path];
    $model->hookRightBeforeSave($missingSize);
    fileSizeExpectSame(1536, $missingSize['size'], 'a missing file size must be filled in bytes');

    $knownSize = ['size' => 777, 'dir' => $path];
    $model->hookRightBeforeSave($knownSize);
    fileSizeExpectSame(777, $knownSize['size'], 'an existing file size must be preserved');

    $zeroSize = ['size' => 0, 'dir' => $path];
    $model->hookRightBeforeSave($zeroSize);
    fileSizeExpectSame(0, $zeroSize['size'], 'a known zero-byte size must be preserved');

    $missingFile = ['size' => null, 'dir' => $path . '.missing'];
    $model->hookRightBeforeSave($missingFile);
    fileSizeExpectSame(null, $missingFile['size'], 'a missing physical file must not block metadata updates');
}
finally {
    @unlink($path);
}

echo "PASS: file model size backfill\n";
