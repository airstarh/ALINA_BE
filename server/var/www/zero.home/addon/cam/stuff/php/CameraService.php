<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;
use Throwable;

final class CameraService
{
    private const SECTIONS = [
        'general' => ['label' => 'General', 'command' => 'General'],
        'network' => ['label' => 'Network', 'command' => 'NetWork'],
        'camera' => ['label' => 'Camera / image', 'command' => 'Camera'],
        'detection' => ['label' => 'Detection', 'command' => 'Detect'],
        'encoding' => ['label' => 'Encoding', 'command' => 'Simplify.Encode'],
        'advanced_encoding' => ['label' => 'Advanced encoding', 'command' => 'AVEnc'],
        'recording' => ['label' => 'Recording', 'command' => 'Record'],
        'storage' => ['label' => 'Storage', 'command' => 'Storage'],
        'alarms' => ['label' => 'Alarms', 'command' => 'Alarm'],
        'uart' => ['label' => 'UART / RS-485 / PTZ', 'command' => 'Uart'],
        'video_ui' => ['label' => 'OSD and volume', 'command' => 'fVideo'],
        'capabilities' => ['label' => 'Capabilities', 'command' => 'SystemFunction'],
        'system' => ['label' => 'System', 'command' => 'SystemInfo'],
        'ability' => ['label' => 'Ability', 'command' => 'Ability'],
    ];

    public function __construct(
        private DvripReader $reader,
        private string $cameraId,
        private string $cameraName,
    ) {
    }

    public function sections(): array
    {
        $sections = [];
        foreach (self::SECTIONS as $id => $definition) {
            $sections[] = ['id' => $id, 'name' => $definition['label']];
        }
        return $sections;
    }

    public function status(): array
    {
        return [
            'camera' => ['id' => $this->cameraId, 'name' => $this->cameraName],
            'system' => $this->sanitize($this->reader->getSystemInfo()),
        ];
    }

    public function config(string $section): mixed
    {
        if (!array_key_exists($section, self::SECTIONS)) {
            throw new InvalidArgumentException('Unknown configuration section.');
        }

        $command = self::SECTIONS[$section]['command'];
        $value = match ($command) {
            'SystemInfo' => $this->reader->getSystemInfo(),
            'SystemFunction' => $this->reader->getSystemFunction(),
            default => $this->reader->getConfig($command),
        };

        return $this->sanitize($value);
    }

    public function all(): array
    {
        $results = [];
        foreach (array_keys(self::SECTIONS) as $section) {
            try {
                $results[$section] = [
                    'ok' => true,
                    'data' => $this->config($section),
                    'error' => null,
                ];
            } catch (Throwable $exception) {
                $code = $exception instanceof DvripException
                    ? $exception->publicCode()
                    : 'section_error';
                $results[$section] = [
                    'ok' => false,
                    'data' => null,
                    'error' => [
                        'code' => $code,
                        'message' => $this->sanitizeMessage($exception->getMessage()),
                    ],
                ];
            }
        }
        return $results;
    }

    public function close(): void
    {
        $this->reader->close();
    }

    private function sanitize(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSecretKey($key)) {
            return '[redacted]';
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $childKey => $childValue) {
                $clean[$childKey] = $this->sanitize(
                    $childValue,
                    is_string($childKey) ? $childKey : null
                );
            }
            return $clean;
        }

        if (is_string($value)) {
            return $this->sanitizeString($value);
        }

        return $value;
    }

    private function isSecretKey(string $key): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', $key) ?? $key);
        if (preg_match('/password|passwd|pwd|token|secret|credential/', $normalized) === 1) {
            return true;
        }

        return in_array($normalized, [
            'key', 'wifikey', 'ddnskey', 'privatekey', 'accesskey',
            'authkey', 'encryptionkey', 'apikey',
        ], true);
    }

    private function sanitizeString(string $value): string
    {
        $value = preg_replace('#(?<=://)[^/@\s]+@#', '[redacted]@', $value) ?? $value;
        return preg_replace_callback(
            '/([?&](?:password|passwd|pwd|token|secret|credential|api_?key|access_?key)=)[^&#]*/i',
            static fn (array $match): string => $match[1] . '%5Bredacted%5D',
            $value
        ) ?? $value;
    }

    private function sanitizeMessage(string $message): string
    {
        return $this->sanitizeString($message);
    }
}
