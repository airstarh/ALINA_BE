<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;
use Throwable;

require_once __DIR__ . '/CameraAccess.php';
require_once __DIR__ . '/OnvifAccounts.php';
require_once __DIR__ . '/OnvifPtz.php';
require_once __DIR__ . '/AccessManager.php';

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
        private array $cameraSettings = [],
        private ?\Closure $accessProbe = null,
        private ?\Closure $ptzPulse = null,
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

    public function availableEditors(): array
    {
        return (new NetworkServiceEditor($this->reader, $this->cameraSettings['alarm_sound'] ?? []))->available(
            $this->reader->getSystemFunction(),
            $this->reader->getSystemInfo(),
        );
    }

    public function editor(string $id): array
    {
        return (new NetworkServiceEditor($this->reader, $this->cameraSettings['alarm_sound'] ?? []))->get($id);
    }

    public function updateEditor(string $id, array $input): array
    {
        return (new NetworkServiceEditor($this->reader, $this->cameraSettings['alarm_sound'] ?? []))->update($id, $input);
    }

    public function status(): array
    {
        return [
            'camera' => ['id' => $this->cameraId, 'name' => $this->cameraName],
            'system' => $this->sanitize($this->reader->getSystemInfo()),
        ];
    }

    public function accessPoints(): array
    {
        try {
            $network = $this->reader->getConfig('NetWork');
        } catch (DvripException $error) {
            if (!in_array($error->publicCode(), ['device_unsupported', 'device_config_missing'], true)) throw $error;
            $network = [];
        }
        $network = is_array($network) ? $network : [];
        $roles = CameraAccess::ports($this->cameraSettings, $network, CameraAccess::correspondent($this->reader));
        $ports = array_values(array_unique(array_merge([21, 22, 23, 80, 443, 554, 8000, 8080, 8443, 8899, 34567, 34599, 37777], array_values($roles))));
        $observed = $this->accessProbe !== null
            ? ($this->accessProbe)($this->cameraSettings['host'], $ports, $roles)
            : (new AccessProbe())->discover($this->cameraSettings['host'], $ports, $roles);
        $model = CameraAccess::model($this->cameraSettings, $network, $observed);
        if (isset($observed['onvif']['url']) && $this->accessProbe === null) {
            try {
                $model['onvifUsers'] = $this->onvifAccounts($observed['onvif']['url'])->users();
            } catch (Throwable $error) {
                $model['onvifAccountError'] = $error->getMessage();
            }
        }
        try {
            $users = method_exists($this->reader, 'getUsers') ? $this->reader->getUsers() : [];
        } catch (DvripException $error) {
            if (!in_array($error->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) throw $error;
            $users = [];
        }
        foreach ($model['points'] as &$point) {
            $mapped = $point['id'] === 'dvrip' || in_array($point['id'], $this->cameraSettings['access']['account_services'] ?? [], true);
            $point['accountManager'] = $mapped && method_exists($this->reader, 'changePassword')
                && in_array($this->cameraSettings['dvrip']['username'], array_column($users, 'Name'), true);
            if ($point['accountManager']) {
                $point['username'] = $this->cameraSettings['dvrip']['username'];
                $point['password'] = $this->cameraSettings['dvrip']['password'];
            }
        }
        unset($point);
        $model['accounts'] = array_map(fn ($user) => ['username' => $user['Name'] ?? '',
            'reserved' => (bool) ($user['Reserved'] ?? false),
            'password' => ($user['Name'] ?? null) === $this->cameraSettings['dvrip']['username']
                ? $this->cameraSettings['dvrip']['password'] : null], $users);
        try {
            $model['groups'] = method_exists($this->reader, 'getGroups') ? array_column($this->reader->getGroups(), 'Name') : [];
        } catch (DvripException $error) {
            if (!in_array($error->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) throw $error;
            $model['groups'] = [];
        }
        return (new AccessManager($this->reader, $this->cameraSettings))->model($model);
    }

    public function saveAccessChanges(array $input, \Closure $persist): array
    {
        $manager = new AccessManager($this->reader, $this->cameraSettings);
        $result = $manager->save($input, $persist);
        $this->cameraSettings = $manager->settings();
        $result['model'] = $this->accessPoints();
        return $result;
    }

    public function updateAccess(array $input, \Closure $persist): array
    {
        if (($input['service'] ?? null) === 'onvif') {
            $network = $this->reader->getConfig('NetWork');
            $roles = CameraAccess::ports($this->cameraSettings, $network);
            $observed = (new AccessProbe())->discover($this->cameraSettings['host'], array_values($roles), $roles);
            if (!isset($observed['onvif']['url'])) throw new InvalidArgumentException('ONVIF device service is not available.');
            return $this->onvifAccounts($observed['onvif']['url'])->save($input);
        }
        if (($input['service'] ?? null) === 'device-user-create') {
            if (array_diff(array_keys($input), ['service', 'username', 'password', 'group']) !== []
                || !is_string($input['username'] ?? null) || !preg_match('/^[a-zA-Z0-9_.-]{1,32}$/D', $input['username'])
                || !is_string($input['password'] ?? null) || strlen($input['password']) > 64
                || preg_match('/[\x00-\x1f\x7f]/', $input['password']) || !is_string($input['group'] ?? null)
                || !method_exists($this->reader, 'addUser')) throw new InvalidArgumentException('Invalid camera user creation request.');
            $this->reader->addUser($input['username'], $input['password'], $input['group']);
            if (!in_array($input['username'], array_column($this->reader->getUsers(), 'Name'), true)) {
                throw new InvalidArgumentException('Camera did not return the created account.');
            }
            return ['saved' => true, 'message' => 'Camera account created and read back. Refresh to see it in the account list.'];
        }
        if (($input['service'] ?? null) === 'device-user-password') {
            if (array_diff(array_keys($input), ['service', 'username', 'password', 'previousPassword']) !== []
                || !is_string($input['username'] ?? null) || !is_string($input['previousPassword'] ?? null)
                || !is_string($input['password'] ?? null) || strlen($input['password']) > 64
                || preg_match('/[\x00-\x1f\x7f]/', $input['password']) || !method_exists($this->reader, 'changePassword')
                || !in_array($input['username'], array_column($this->reader->getUsers(), 'Name'), true)
                || $input['username'] === $this->cameraSettings['dvrip']['username']) {
                throw new InvalidArgumentException('Invalid camera account password request.');
            }
            $this->reader->changePassword($input['username'], $input['previousPassword'], $input['password']);
            return ['saved' => true, 'message' => 'Camera accepted this account password change.'];
        }
        if (array_diff(array_keys($input), ['service', 'password', 'previousPassword']) !== []
            || ($input['service'] ?? null) !== 'dvrip' || !is_string($input['password'] ?? null)
            || !is_string($input['previousPassword'] ?? null) || strlen($input['password']) > 64
            || preg_match('/[\x00-\x1f\x7f]/', $input['password'])) {
            throw new InvalidArgumentException('Invalid camera account password change.');
        }
        $account = $this->cameraSettings['dvrip'];
        if (!hash_equals($account['password'], $input['previousPassword'])) {
            throw new InvalidArgumentException('Camera account changed since this form was loaded. Refresh before saving.');
        }
        if (!method_exists($this->reader, 'changePassword') || !method_exists($this->reader, 'login')) {
            throw new InvalidArgumentException('Camera account changes are unavailable for this connection.');
        }
        if ($input['password'] !== $account['password']) {
            $this->reader->changePassword($account['username'], $account['password'], $input['password']);
            try {
                if ($this->reader instanceof DvripClient) {
                    $verify = new DvripClient(new StreamDvripTransport($this->cameraSettings['host'], $account['port'], (float) ($account['timeout'] ?? 5)));
                    try { $verify->login($account['username'], $input['password']); }
                    finally { $verify->close(); }
                } else {
                    $this->reader->login($account['username'], $input['password']);
                }
                $persist($input['password']);
            } catch (Throwable $error) {
                // Retain application access if verification or local credential persistence fails.
                $this->reader->changePassword($account['username'], $input['password'], $account['password']);
                throw $error;
            }
            $this->cameraSettings['dvrip']['password'] = $input['password'];
        }
        return ['saved' => true, 'message' => 'Camera account password saved; a fresh DVRIP login succeeded.'];
    }

    private function onvifAccounts(string $url): OnvifAccounts
    {
        return new OnvifAccounts($url, $this->cameraSettings['access']['credentials']['onvif'] ?? $this->cameraSettings['dvrip']);
    }

    public function reboot(): array
    {
        $this->reader->reboot();
        return ['accepted' => true];
    }

    public function ptzCapabilities(): array
    {
        $other = $this->reader->getSystemFunction()['OtherFunction'] ?? [];
        $available = is_array($other) && ($other['SupportPTZDirectionControl'] ?? false) === true;
        return [
            'available' => $available,
            'directions' => $available
                ? ['up', 'up-right', 'right', 'down-right', 'down', 'down-left', 'left', 'up-left'] : [],
            'zoom' => $available,
            'speeds' => $available ? ['low', 'medium', 'high'] : [],
            'defaultSpeed' => 'medium',
            'pulseDurationMs' => 180,
            'presets' => $available && ($other['SupportSetPTZPresetAttribute'] ?? false) === true,
            'presetMinimum' => 1,
            'presetMaximum' => 255,
        ];
    }

    public function ptzCommand(array $input): array
    {
        $capabilities = $this->ptzCapabilities();
        if (!$capabilities['available']) {
            throw new InvalidArgumentException('PTZ movement is not available on this camera.');
        }
        $operation = $input['operation'] ?? null;
        if ($operation === 'move') {
            if (array_diff(array_keys($input), ['operation', 'direction', 'speed', 'durationMs']) !== []
                || !is_string($input['direction'] ?? null)
                || !in_array($input['direction'], $capabilities['directions'], true)
                || !is_string($input['speed'] ?? null)
                || !in_array($input['speed'], $capabilities['speeds'], true)
                || !is_int($input['durationMs'] ?? null)
                || $input['durationMs'] < 80 || $input['durationMs'] > 400) {
                throw new InvalidArgumentException('Invalid PTZ movement command.');
            }
            $pulse = $this->ptzPulse ?? function (string $direction, string $speed, int $durationMs): void {
                $this->onvifPtz()->pulse($direction, $speed, $durationMs);
            };
            $pulse($input['direction'], $input['speed'], $input['durationMs']);
            return ['accepted' => true];
        }

        if (!in_array($operation, ['goto', 'save', 'delete'], true)
            || array_diff(array_keys($input), ['operation', 'preset']) !== []
            || !$capabilities['presets'] || !is_int($input['preset'] ?? null)
            || $input['preset'] < 1 || $input['preset'] > 255
            || !method_exists($this->reader, 'ptzPreset')) {
            throw new InvalidArgumentException('Invalid or unavailable PTZ preset command.');
        }
        $this->reader->ptzPreset($operation, $input['preset']);
        return ['accepted' => true];
    }

    private function onvifPtz(): OnvifPtz
    {
        $host = $this->cameraSettings['host'] ?? '';
        if (!is_string($host) || $host === '' || !isset($this->cameraSettings['dvrip'])) {
            throw new InvalidArgumentException('ONVIF PTZ connection is not configured.');
        }
        $ports = CameraAccess::ports($this->cameraSettings, [], CameraAccess::correspondent($this->reader));
        $port = $ports['onvif'] ?? null;
        if (!is_int($port)) throw new InvalidArgumentException('ONVIF PTZ port is not configured.');
        $address = str_contains($host, ':') ? '[' . trim($host, '[]') . ']' : $host;
        $base = 'http://' . $address . ':' . $port . '/onvif/';
        $credentials = $this->cameraSettings['access']['credentials']['onvif'] ?? $this->cameraSettings['dvrip'];
        return new OnvifPtz($base . 'Media', $base . 'PTZ', $credentials);
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

    public function testSmtp(): array
    {
        $capabilities = $this->reader->getSystemFunction();
        if (($capabilities['OtherFunction']['SupportMailTest'] ?? false) !== true) {
            throw new DvripException('device_unsupported', 'Camera does not advertise email testing support.');
        }

        $email = $this->smtpBranch();
        foreach (['Enable', 'UseSSL', 'SendAddr', 'Recievers', 'Schedule', 'Title'] as $field) {
            if (!array_key_exists($field, $email)) {
                throw new DvripException('device_config_missing', 'Camera returned an incomplete SMTP configuration object.');
            }
        }
        $settings = [
            'Enable' => $email['Enable'],
            'MailServer' => $email['MailServer'],
            'UseSSL' => $email['UseSSL'],
            'SendAddr' => $email['SendAddr'],
            'Recievers' => $email['Recievers'],
            'Schedule' => $email['Schedule'],
            'Title' => $email['Title'],
        ];
        if (!$this->reader->testMail($settings)) {
            throw new DvripException('device_error', 'Camera rejected the SMTP test request.');
        }
        return ['accepted' => true];
    }

    public function testFtp(): array
    {
        $capabilities = $this->reader->getSystemFunction();
        if (($capabilities['OtherFunction']['SupportFTPTest'] ?? false) !== true) {
            throw new DvripException('device_unsupported', 'Camera does not advertise FTP testing support.');
        }

        $ftp = $this->reader->getConfig('NetWork.NetFTP');
        if (!is_array($ftp) || !is_array($ftp['Server'] ?? null)) {
            throw new DvripException('device_config_missing', 'Camera returned an invalid FTP configuration.');
        }
        foreach (['Enable', 'Directory', 'MaxFileLen'] as $field) {
            if (!array_key_exists($field, $ftp)) {
                throw new DvripException('device_config_missing', 'Camera returned an incomplete FTP configuration.');
            }
        }
        foreach (['Address', 'Anonymity', 'Name', 'Password', 'Port', 'UserName'] as $field) {
            if (!array_key_exists($field, $ftp['Server'])) {
                throw new DvripException('device_config_missing', 'Camera returned an incomplete FTP server.');
            }
        }
        $settings = [
            'Directory' => $ftp['Directory'],
            'Enable' => $ftp['Enable'],
            'MaxFileLen' => $ftp['MaxFileLen'],
            'Server' => $ftp['Server'],
        ];
        try {
            $accepted = $this->reader->testFtp($settings);
        } catch (DvripException $exception) {
            if ($exception->publicCode() !== 'device_error') {
                throw $exception;
            }
            throw new DvripException('ftp_test_failed', 'Camera reported the FTP test failed.', $exception);
        }
        if (!$accepted) {
            throw new DvripException('ftp_test_failed', 'Camera reported the FTP test failed.');
        }
        return ['accepted' => true];
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
