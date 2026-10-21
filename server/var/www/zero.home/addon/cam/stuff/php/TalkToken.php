<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;
use RuntimeException;

final class TalkToken
{
    public function __construct(
        private string $secret,
        private int $ttlSeconds = 30,
    ) {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('Camera talk token secret must contain at least 32 bytes.');
        }
        if ($ttlSeconds < 1 || $ttlSeconds > 300) {
            throw new InvalidArgumentException('Camera talk token lifetime is invalid.');
        }
    }

    public static function fromEnvironment(): self
    {
        $secret = getenv('ALINA_CAM_TALK_SECRET');
        if (!is_string($secret) || $secret === '') {
            throw new RuntimeException('Camera talkback service is not configured.');
        }

        return new self($secret);
    }

    /** @return array{token: string, expiresAt: int} */
    public function issue(string $cameraId, int $now): array
    {
        $this->validateCameraId($cameraId);
        $expiresAt = $now + $this->ttlSeconds;
        $payload = json_encode([
            'camera' => $cameraId,
            'exp' => $expiresAt,
            'nonce' => self::encode(random_bytes(12)),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $encodedPayload = self::encode($payload);
        $signature = self::encode(hash_hmac('sha256', $encodedPayload, $this->secret, true));

        return ['token' => $encodedPayload . '.' . $signature, 'expiresAt' => $expiresAt];
    }

    public function verify(string $token, int $now): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException('Camera talk token is invalid.');
        }

        [$encodedPayload, $encodedSignature] = $parts;
        $expected = self::encode(hash_hmac('sha256', $encodedPayload, $this->secret, true));
        if (!hash_equals($expected, $encodedSignature)) {
            throw new InvalidArgumentException('Camera talk token is invalid.');
        }

        try {
            $payload = json_decode(self::decode($encodedPayload), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Camera talk token is invalid.');
        }
        if (!is_array($payload)
            || !is_string($payload['camera'] ?? null)
            || !is_int($payload['exp'] ?? null)
            || !is_string($payload['nonce'] ?? null)
            || $payload['exp'] <= $now
        ) {
            throw new InvalidArgumentException('Camera talk token is invalid or expired.');
        }
        $this->validateCameraId($payload['camera']);

        return $payload['camera'];
    }

    private function validateCameraId(string $cameraId): void
    {
        if (preg_match('/^[a-z][a-z0-9_-]*$/D', $cameraId) !== 1) {
            throw new InvalidArgumentException('Camera talk token camera is invalid.');
        }
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decode(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1) {
            throw new InvalidArgumentException('Camera talk token is invalid.');
        }
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Camera talk token is invalid.');
        }
        return $decoded;
    }
}
