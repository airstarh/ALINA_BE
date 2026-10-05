<?php

declare(strict_types=1);

namespace Cam;

interface DvripTransport
{
    public function connect(): void;

    public function writeAll(string $bytes): void;

    public function readExact(int $length): string;

    public function close(): void;
}
