<?php

declare(strict_types=1);

namespace Cam;

interface DvripDuplexTransport extends DvripTransport
{
    public function readAvailable(int $maximumBytes = 65536): string;

    public function setBlocking(bool $blocking): void;

    /** @return resource */
    public function stream();
}
