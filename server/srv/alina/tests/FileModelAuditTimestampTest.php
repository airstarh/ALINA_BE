<?php

declare(strict_types=1);

require_once __DIR__ . '/../mvc/Model/_BaseAlinaModel.php';
require_once __DIR__ . '/../mvc/Model/file.php';

use alina\mvc\Model\file;

$model = (new ReflectionClass(file::class))->newInstanceWithoutConstructor();

if (! $model->addAuditInfo) {
    fwrite(STDERR, "FAIL: file model must use dynamic base-model audit timestamps\n");
    exit(1);
}

echo "PASS: file model uses dynamic base-model audit timestamps\n";
