<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;
use Throwable;

final class LiveAuthorization
{
    public function __construct(
        private Config $config,
        private TalkToken $tokens,
    ) {
    }

    public static function requestQuery(array $server, array $query): array
    {
        $originalUri = $server['HTTP_X_ORIGINAL_URI'] ?? null;
        if (!is_string($originalUri) || $originalUri === '') {
            return $query;
        }

        $queryString = parse_url($originalUri, PHP_URL_QUERY);
        if (!is_string($queryString)) {
            return [];
        }
        parse_str($queryString, $parsed);

        return array_intersect_key($parsed, array_flip(['camera', 'src', 'token']));
    }

    public function authorize(array $query, int $now): void
    {
        try {
            $cameraId = $query['camera'] ?? null;
            $stream = $query['src'] ?? null;
            $token = $query['token'] ?? null;
            if (!is_string($cameraId) || !is_string($stream) || !is_string($token)
                || $cameraId === '' || $stream === '' || $token === '') {
                throw new InvalidArgumentException('Missing authorization data.');
            }

            $this->config->camera($cameraId);
            if ($this->tokens->verify($token, $now) !== $cameraId) {
                throw new InvalidArgumentException('Camera token mismatch.');
            }
            if ((new LiveStream($this->config))->relayName($cameraId) !== $stream) {
                throw new InvalidArgumentException('Camera stream mismatch.');
            }
        } catch (Throwable) {
            throw new InvalidArgumentException('Live stream authorization failed.');
        }
    }
}
