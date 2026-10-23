<?php

declare(strict_types=1);

require_once __DIR__ . '/../AppExceptionCatcher.php';

use alina\AppExceptionCatcher;

final class BootstrapSafeExceptionCatcher extends AppExceptionCatcher
{
    public bool $processed = false;
    public string $capturedMessage = '';

    protected function processError()
    {
        $this->processed       = true;
        $this->capturedMessage = $this->eString;
    }
}

$catcher = BootstrapSafeExceptionCatcher::obj();
$catcher->exception(new RuntimeException('original startup failure'), false);

if (! $catcher->processed) {
    fwrite(STDERR, "FAIL: the original exception must still reach normal processing when framework helpers are unavailable\n");
    exit(1);
}

if ($catcher->capturedMessage !== 'original startup failure') {
    fwrite(STDERR, "FAIL: a secondary bootstrap failure replaced the original exception\n");
    exit(1);
}

fwrite(STDOUT, "PASS: bootstrap-safe exception handling\n");
