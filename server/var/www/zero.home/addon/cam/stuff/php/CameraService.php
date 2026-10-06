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
        return self::sectionList();
    }

    public static function sectionList(): array
    {
        $sections = [];
        foreach (self::SECTIONS as $id => $definition) {
            $sections[] = ['id' => $id, 'name' => $definition['label']];
        }
        return $sections;
    }

    public static function editorList(): array
    {
        return NetworkServiceEditor::list();
    }

    public function editor(string $id): array
    {
        return (new NetworkServiceEditor($this->reader))->get($id);
    }

    public function updateEditor(string $id, array $input): array
    {
        return (new NetworkServiceEditor($this->reader))->update($id, $input);
    }

    public function status(): array
    {
        return [
            'camera' => ['id' => $this->cameraId, 'name' => $this->cameraName],
            'system' => $this->sanitize($this->reader->getSystemInfo()),
        ];
    }

    public function wifiAccessPoints(): array
    {
        $result = $this->reader->getInfo('WifiAP');
        $items = is_array($result) ? ($result['WifiAP'] ?? null) : null;
        if (!is_array($items)) {
            throw new DvripException('device_config_missing', 'Camera returned an invalid Wi-Fi scan result.');
        }

        $accessPoints = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new DvripException('device_config_missing', 'Camera returned an invalid Wi-Fi access point.');
            }
            foreach (['SSID', 'Auth', 'EncrypType', 'Channel', 'NetType', 'RSSI'] as $key) {
                if (!array_key_exists($key, $item)) {
                    throw new DvripException('device_config_missing', 'Camera returned an incomplete Wi-Fi access point.');
                }
            }
            $accessPoints[] = [
                'ssid' => (string) $item['SSID'],
                'authentication' => (string) $item['Auth'],
                'encryption' => (string) $item['EncrypType'],
                'channel' => (int) $item['Channel'],
                'networkType' => (string) $item['NetType'],
                'signal' => (string) $item['RSSI'],
            ];
        }

        return ['count' => count($accessPoints), 'accessPoints' => $accessPoints];
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

    public function smtp(): array
    {
        return $this->smtpModel($this->smtpBranch());
    }

    public function updateSmtp(array $input): array
    {
        $clean = $this->validateSmtpInput($input);
        $current = $this->smtpBranch();
        $updated = $current;
        $updated['Enable'] = $clean['enable'];
        $updated['MailServer']['Name'] = $clean['server'];
        $updated['MailServer']['Port'] = $clean['port'];
        $updated['MailServer']['UserName'] = $clean['username'];
        $updated['MailServer']['Password'] = $clean['password'];
        $updated['UseSSL'] = $clean['useSsl'];
        $updated['SendAddr'] = $clean['sendAddress'];
        $updated['Recievers'] = array_pad($clean['recipients'], 5, 'none');
        $updated['Title'] = $clean['title'];

        $this->reader->setConfig('NetWork.NetEmail', $updated);
        $verified = $this->smtpBranch();
        if ($verified !== $updated) {
            throw new DvripException(
                'write_verification_failed',
                'SMTP configuration read-back did not match the submitted object.'
            );
        }
        return $this->smtpModel($verified);
    }

    public function close(): void
    {
        $this->reader->close();
    }

    private function smtpBranch(): array
    {
        $value = $this->reader->getConfig('NetWork.NetEmail');
        if (!is_array($value)
            || !isset($value['MailServer'])
            || !is_array($value['MailServer'])
            || !array_key_exists('Password', $value['MailServer'])
            || !isset($value['Recievers'])
            || !is_array($value['Recievers'])
        ) {
            throw new DvripException(
                'device_config_missing',
                'Camera returned an incomplete SMTP configuration object.'
            );
        }
        return $value;
    }

    private function smtpModel(array $value): array
    {
        $server = $value['MailServer'];
        return [
            'enable' => (bool) ($value['Enable'] ?? false),
            'server' => (string) ($server['Name'] ?? ''),
            'port' => (int) ($server['Port'] ?? 0),
            'username' => (string) ($server['UserName'] ?? ''),
            'password' => (string) $server['Password'],
            'useSsl' => (int) ($value['UseSSL'] ?? 0),
            'sendAddress' => (string) ($value['SendAddr'] ?? ''),
            'recipients' => array_values(array_map('strval', $value['Recievers'])),
            'title' => (string) ($value['Title'] ?? ''),
        ];
    }

    private function validateSmtpInput(array $input): array
    {
        $required = [
            'enable', 'server', 'port', 'username', 'password',
            'useSsl', 'sendAddress', 'recipients', 'title',
        ];
        foreach ($required as $key) {
            if (!array_key_exists($key, $input)) {
                throw new InvalidArgumentException("SMTP field {$key} is required.");
            }
        }
        if (!is_bool($input['enable'])
            || !is_int($input['port'])
            || $input['port'] < 1
            || $input['port'] > 65535
            || !is_int($input['useSsl'])
            || !in_array($input['useSsl'], [0, 1], true)
            || !is_array($input['recipients'])
            || count($input['recipients']) > 5
        ) {
            throw new InvalidArgumentException('SMTP field types or ranges are invalid.');
        }
        foreach (['server', 'username', 'password', 'sendAddress', 'title'] as $key) {
            if (!is_string($input[$key]) || strlen($input[$key]) > 255) {
                throw new InvalidArgumentException("SMTP field {$key} is invalid.");
            }
        }
        if (trim($input['server']) === '') {
            throw new InvalidArgumentException('SMTP server is required.');
        }
        foreach ($input['recipients'] as $recipient) {
            if (!is_string($recipient) || strlen($recipient) > 255) {
                throw new InvalidArgumentException('SMTP recipient is invalid.');
            }
        }
        return [
            ...$input,
            'server' => trim($input['server']),
            'username' => trim($input['username']),
            'sendAddress' => trim($input['sendAddress']),
            'recipients' => array_values(array_map('trim', $input['recipients'])),
            'title' => trim($input['title']),
        ];
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
            'key', 'keys', 'wifikey', 'ddnskey', 'privatekey', 'accesskey',
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
