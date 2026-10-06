<?php

declare(strict_types=1);

namespace Cam;

interface DvripReader
{
    public function getConfig(string $command): mixed;

    public function setConfig(string $command, mixed $value): bool;

    public function getSystemInfo(): array;

    public function getInfo(string $command): mixed;

    public function getSystemFunction(): array;

    public function close(): void;
}
