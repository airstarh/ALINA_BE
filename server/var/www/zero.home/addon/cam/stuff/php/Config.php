<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;
use UnexpectedValueException;

final class Config
{
    private function __construct(
        private array $cameras,
        private string $defaultCameraId,
        private string $configPath,
    ) {
    }

    public static function load(string $path): self
    {
        if (!is_file($path)) {
            throw new UnexpectedValueException('Camera configuration file is missing.');
        }

        $data = require $path;
        if (!is_array($data)) {
            throw new UnexpectedValueException('Camera configuration must return an array.');
        }

        $default = $data['default'] ?? null;
        $cameras = $data['cameras'] ?? null;
        if (!is_string($default) || !is_array($cameras) || $cameras === []) {
            throw new UnexpectedValueException('Camera configuration requires default and cameras.');
        }

        foreach ($cameras as $id => $camera) {
            self::validateCamera($id, $camera);
        }

        if (!array_key_exists($default, $cameras)) {
            throw new UnexpectedValueException('Default camera is not configured.');
        }

        foreach ($cameras as $id => &$camera) {
            $statePath = dirname($path) . '/.access-' . $id . '-' . substr(hash('sha256', $path), 0, 12) . '.php';
            if (is_file($statePath)) {
                $state = require $statePath;
                if (is_array($state) && ($state['host'] ?? null) === $camera['host']
                    && is_string($state['password'] ?? null) && ($state['username'] ?? null) === $camera['dvrip']['username']) {
                    $camera['dvrip']['password'] = $state['password'];
                    if (is_int($state['port'] ?? null) && $state['port'] >= 1 && $state['port'] <= 65535) $camera['dvrip']['port'] = $state['port'];
                    foreach ($state['ports'] ?? [] as $service => $port) {
                        if (in_array($service, ['http', 'https', 'rtsp', 'onvif'], true) && is_int($port) && $port >= 1 && $port <= 65535) {
                            $camera['access']['ports'][$service] = $port;
                        }
                    }
                    $camera['accessOrigins'] = is_array($state['origins'] ?? null) ? $state['origins'] : [];
                }
            }
        }
        unset($camera);
        return new self($cameras, $default, $path);
    }

    public function prepareCredentialSave(string $id): void
    {
        $this->camera($id);
        if (!is_writable(dirname($this->configPath))) {
            throw new InvalidArgumentException('Camera configuration directory is not writable; password was not changed.');
        }
    }

    public function saveAccountPassword(string $id, string $password): void
    {
        $this->saveAccessState($id, ['password' => $password]);
    }

    public function saveAccessState(string $id, array $updates): void
    {
        $camera = $this->camera($id);
        $path = dirname($this->configPath) . '/.access-' . $id . '-' . substr(hash('sha256', $this->configPath), 0, 12) . '.php';
        $state = is_file($path) ? require $path : [];
        if (!is_array($state) || ($state['host'] ?? null) !== $camera['host']) $state = [];
        $state = array_replace(['host' => $camera['host'], 'username' => $camera['dvrip']['username'],
            'password' => $camera['dvrip']['password'], 'port' => $camera['dvrip']['port']], $state, $updates);
        $temporary = dirname($path) . '/.access-write-' . bin2hex(random_bytes(12)) . '.php';
        $mask = umask(0077);
        try { $handle = fopen($temporary, 'x'); } finally { umask($mask); }
        if ($handle === false) throw new \RuntimeException('Could not persist camera credentials.');
        try {
            $content = "<?php\nreturn " . var_export($state, true) . ";\n";
            if (fwrite($handle, $content) !== strlen($content)) {
                throw new \RuntimeException('Could not persist camera credentials.');
            }
            fclose($handle);
            $handle = null;
            if (!rename($temporary, $path)) {
                throw new \RuntimeException('Could not persist camera credentials.');
            }
            if (function_exists('opcache_invalidate')) opcache_invalidate($path, true);
            $this->cameras[$id]['dvrip']['password'] = $state['password'];
            $this->cameras[$id]['dvrip']['port'] = $state['port'];
            foreach ($state['ports'] ?? [] as $service => $port) $this->cameras[$id]['access']['ports'][$service] = $port;
            $this->cameras[$id]['accessOrigins'] = $state['origins'] ?? [];
        } finally {
            if (is_resource($handle)) fclose($handle);
            if (is_file($temporary)) unlink($temporary);
        }
    }

    public function publicCameras(): array
    {
        $public = [];
        foreach ($this->cameras as $id => $camera) {
            $public[] = [
                'id' => $id,
                'name' => $camera['name'],
                'talkback' => ($camera['talkback']['speaker'] ?? false) === true,
            ];
        }

        return $public;
    }

    public function defaultCameraId(): string
    {
        return $this->defaultCameraId;
    }

    public function camera(string $id): array
    {
        if (!array_key_exists($id, $this->cameras)) {
            throw new InvalidArgumentException('Unknown camera.');
        }

        return $this->cameras[$id];
    }

    private static function validateCamera(mixed $id, mixed $camera): void
    {
        if (!is_string($id) || preg_match('/^[a-z][a-z0-9_-]*$/D', $id) !== 1) {
            throw new UnexpectedValueException('Camera ID is invalid.');
        }

        if (!is_array($camera)) {
            throw new UnexpectedValueException("Camera {$id} must be an array.");
        }

        $name = $camera['name'] ?? null;
        $host = $camera['host'] ?? null;
        $dvrip = $camera['dvrip'] ?? null;
        if (!is_string($name) || trim($name) === '' || !is_string($host) || trim($host) === '') {
            throw new UnexpectedValueException("Camera {$id} requires a name and host.");
        }
        if (!is_array($dvrip)) {
            throw new UnexpectedValueException("Camera {$id} requires DVRIP settings.");
        }

        foreach (['port', 'username', 'password'] as $key) {
            if (!array_key_exists($key, $dvrip)) {
                throw new UnexpectedValueException("Camera {$id} DVRIP setting {$key} is missing.");
            }
        }

        if (!is_int($dvrip['port']) || $dvrip['port'] < 1 || $dvrip['port'] > 65535) {
            throw new UnexpectedValueException("Camera {$id} DVRIP port is invalid.");
        }
        if (!is_string($dvrip['username']) || !is_string($dvrip['password'])) {
            throw new UnexpectedValueException("Camera {$id} DVRIP credentials are invalid.");
        }
        if (array_key_exists('timeout', $dvrip)
            && (!is_int($dvrip['timeout']) && !is_float($dvrip['timeout']) || $dvrip['timeout'] <= 0)
        ) {
            throw new UnexpectedValueException("Camera {$id} DVRIP timeout is invalid.");
        }

        if (array_key_exists('access', $camera)) {
            $access = $camera['access'];
            if (!is_array($access) || array_diff(array_keys($access), ['ports', 'credentials', 'notes', 'account_services']) !== []) {
                throw new UnexpectedValueException("Camera {$id} access settings are invalid.");
            }
            if (isset($access['account_services']) && (!is_array($access['account_services'])
                || array_diff($access['account_services'], ['http', 'https', 'rtsp', 'onvif']) !== [])) {
                throw new UnexpectedValueException("Camera {$id} shared account service mapping is invalid.");
            }
            if (isset($access['notes'])) {
                if (!is_array($access['notes']) || array_diff(array_keys($access['notes']), ['http', 'https', 'rtsp', 'onvif']) !== []) {
                    throw new UnexpectedValueException("Camera {$id} access notes are invalid.");
                }
                foreach ($access['notes'] as $note) if (!is_string($note)) {
                    throw new UnexpectedValueException("Camera {$id} access note must be text.");
                }
            }
            foreach (['ports', 'credentials'] as $kind) {
                if (!array_key_exists($kind, $access)) continue;
                if (!is_array($access[$kind]) || array_diff(array_keys($access[$kind]), ['http', 'https', 'rtsp', 'onvif']) !== []) {
                    throw new UnexpectedValueException("Camera {$id} access {$kind} are invalid.");
                }
                foreach ($access[$kind] as $value) {
                    if ($kind === 'ports') {
                        if (!is_int($value) || $value < 1 || $value > 65535) {
                            throw new UnexpectedValueException("Camera {$id} access port is invalid.");
                        }
                    } elseif (!is_array($value) || !is_string($value['username'] ?? null) || !is_string($value['password'] ?? null)
                        || array_diff(array_keys($value), ['username', 'password']) !== []) {
                        throw new UnexpectedValueException("Camera {$id} access credentials are invalid.");
                    }
                }
            }
        }

        if (array_key_exists('alarm_sound', $camera)) {
            $sound = $camera['alarm_sound'];
            if (!is_array($sound) || array_diff(array_keys($sound), ['supported', 'labels', 'options']) !== []) {
                throw new UnexpectedValueException("Camera {$id} alarm sound settings are invalid.");
            }
            if (array_key_exists('supported', $sound) && !is_bool($sound['supported'])) {
                throw new UnexpectedValueException("Camera {$id} alarm sound support override must be boolean.");
            }
            foreach (['labels', 'options'] as $name) {
                if (!array_key_exists($name, $sound)) continue;
                if (!is_array($sound[$name])) {
                    throw new UnexpectedValueException("Camera {$id} alarm sound {$name} must be a code-to-label map.");
                }
                foreach ($sound[$name] as $code => $label) {
                    if (!is_int($code) || $code < 0 || $code > 65535
                        || !is_string($label) || trim($label) === '') {
                        throw new UnexpectedValueException("Camera {$id} alarm sound {$name} entry is invalid.");
                    }
                }
            }
        }

        if (array_key_exists('talkback', $camera)) {
            $talkback = $camera['talkback'];
            if (!is_array($talkback)
                || array_diff(array_keys($talkback), ['speaker']) !== []
                || !is_bool($talkback['speaker'] ?? null)
            ) {
                throw new UnexpectedValueException("Camera {$id} talkback settings are invalid.");
            }
        }

        if (array_key_exists('live', $camera)) {
            $live = $camera['live'];
            if (!is_array($live)
                || array_diff(array_keys($live), ['rtsp_transport']) !== []
                || !in_array($live['rtsp_transport'] ?? null, ['tcp', 'udp'], true)
            ) {
                throw new UnexpectedValueException("Camera {$id} live-stream settings are invalid.");
            }
        }
    }
}
