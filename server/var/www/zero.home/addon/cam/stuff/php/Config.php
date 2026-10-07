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

        return new self($cameras, $default);
    }

    public function publicCameras(): array
    {
        $public = [];
        foreach ($this->cameras as $id => $camera) {
            $public[] = ['id' => $id, 'name' => $camera['name']];
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
    }
}
