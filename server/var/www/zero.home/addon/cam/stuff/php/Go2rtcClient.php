<?php

declare(strict_types=1);

namespace Cam;

use Closure;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class Go2rtcClient
{
    private const TIMEOUT_SECONDS = 2.0;

    public function __construct(
        private string $baseUrl = 'http://go2rtc:1984/cam-stream',
        private ?Closure $request = null,
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

    public function upsert(string $name, string $source): void
    {
        if (preg_match('/^alina-[a-z][a-z0-9_-]*$/D', $name) !== 1) {
            throw new InvalidArgumentException('Camera relay name is invalid.');
        }
        if ($source === '') {
            throw new InvalidArgumentException('Camera relay source is required.');
        }

        $lookupUrl = $this->baseUrl . '/api/streams?' . http_build_query(
            ['src' => $name],
            '',
            '&',
            PHP_QUERY_RFC3986
        );
        $createUrl = $this->baseUrl . '/api/streams?' . http_build_query(
            ['name' => $name, 'src' => $source],
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $lock = @fopen(sys_get_temp_dir() . '/alina-go2rtc-' . hash('sha256', $name) . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('Camera relay is unavailable.');
        }

        try {
            $response = $this->call('GET', $lookupUrl);
            $status = is_array($response) ? ($response['status'] ?? null) : null;
            if (is_int($status) && $status >= 200 && $status < 300) {
                return;
            }
            if ($status !== 404) {
                throw new RuntimeException('Relay lookup failed.');
            }

            $response = $this->call('PUT', $createUrl);
            $status = is_array($response) ? ($response['status'] ?? null) : null;
            if (!is_int($status) || $status < 200 || $status >= 300) {
                throw new RuntimeException('Relay creation failed.');
            }
        } catch (Throwable) {
            throw new RuntimeException('Camera relay is unavailable.');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function call(string $method, string $url): array
    {
        return $this->request === null
            ? $this->send($method, $url, self::TIMEOUT_SECONDS)
            : ($this->request)($method, $url, self::TIMEOUT_SECONDS);
    }

    /** @return array{status: int, body: string} */
    private function send(string $method, string $url, float $timeout): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => "Connection: close\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
        $status = null;
        if (isset($headers[0]) && preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $headers[0], $matches) === 1) {
            $status = (int) $matches[1];
        }
        if ($body === false || $status === null) {
            throw new RuntimeException('Relay request failed.');
        }

        return ['status' => $status, 'body' => $body];
    }
}
