<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
    ) {
    }

    public function succeeded(): bool
    {
        return $this->exitCode === 0;
    }
}
