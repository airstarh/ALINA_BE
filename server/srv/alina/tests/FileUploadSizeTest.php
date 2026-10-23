<?php

declare(strict_types=1);

require_once __DIR__ . '/../mvc/Controller/FileUpload.php';

use alina\mvc\Controller\FileUpload;

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
    fileSizeExpectSame(1536, FileUpload::fileSizeInBytes($path), 'uploaded file size must be returned in bytes');
}
finally {
    @unlink($path);
}

echo "PASS: uploaded file size in bytes\n";
