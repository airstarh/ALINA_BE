<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use InvalidArgumentException;

final readonly class WakeRequest
{
    private const MAX_BYTES = 4096;

    public function __construct(public string $publicId, public string $secret)
    {
    }

    public static function parse(string $frame): self
    {
        if (strlen($frame) > self::MAX_BYTES) throw new InvalidArgumentException('Wake frame is too large.');
        $data = json_decode(trim($frame), true);
        if (!is_array($data) || array_is_list($data)) throw new InvalidArgumentException('Invalid wake frame.');
        $publicId = $data['publicId'] ?? null;
        $secret = $data['secret'] ?? null;
        if (!is_string($publicId) || !preg_match('/^[A-Za-z0-9-]{1,64}$/D',$publicId)) throw new InvalidArgumentException('Invalid job identifier.');
        if (!is_string($secret) || $secret==='' || strlen($secret)>512) throw new InvalidArgumentException('Invalid wake secret.');
        return new self($publicId,$secret);
    }
}
