<?php

declare(strict_types=1);

namespace Cam;

final class TalkSessionRegistry
{
    /** @var array<string, object> */
    private array $owners = [];

    public function acquire(string $cameraId, object $owner): bool
    {
        if (isset($this->owners[$cameraId])) return false;
        $this->owners[$cameraId] = $owner;
        return true;
    }

    public function release(string $cameraId, object $owner): void
    {
        if (($this->owners[$cameraId] ?? null) === $owner) unset($this->owners[$cameraId]);
    }
}
