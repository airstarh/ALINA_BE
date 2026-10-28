<?php

declare(strict_types=1);

namespace alina\program\socket;

use alina\Services\FileJob\FileJobServerRunner;

error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/../../AppBoot.php';
FileJobServerRunner::go();
