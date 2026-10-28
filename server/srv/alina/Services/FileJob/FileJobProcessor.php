<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

interface FileJobProcessor
{
    public function start(object $job, callable $progress, callable $complete, callable $failure): void;
}
