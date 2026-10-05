<?php

declare(strict_types=1);

namespace Cam;

use RuntimeException;
use Throwable;

final class DvripException extends RuntimeException
{
    public function __construct(
        private string $publicCode,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function publicCode(): string
    {
        return $this->publicCode;
    }
}
