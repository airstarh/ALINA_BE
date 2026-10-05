<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;

final class NetworkServiceEditor
{
    private const DEFINITIONS = [
        'ftp' => [
            'label' => 'FTP', 'branch' => 'NetWork.NetFTP',
            'fields' => [
                'enable' => ['label' => 'FTP uploads', 'type' => 'boolean', 'path' => ['Enable']],
                'server' => ['label' => 'Server', 'type' => 'string', 'path' => ['Server', 'Name']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => ['Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => ['Server', 'Password']],
                'directory' => ['label' => 'Directory', 'type' => 'string', 'path' => ['Directory']],
                'maxFileLen' => ['label' => 'Maximum file length', 'type' => 'integer', 'min' => 1, 'max' => 2147483647, 'path' => ['MaxFileLen']],
            ],
        ],
        'ntp' => [
            'label' => 'NTP / time sync', 'branch' => 'NetWork.NetNTP',
            'fields' => [
                'enable' => ['label' => 'Time synchronization', 'type' => 'boolean', 'path' => ['Enable']],
                'server' => ['label' => 'NTP server', 'type' => 'string', 'path' => ['Server', 'Name']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Server', 'Port']],
                'timeZone' => ['label' => 'Camera timezone code', 'type' => 'integer', 'min' => 0, 'max' => 26, 'path' => ['TimeZone']],
                'updatePeriod' => ['label' => 'Update period (minutes)', 'type' => 'integer', 'min' => 1, 'max' => 1440, 'path' => ['UpdatePeriod']],
            ],
        ],
        'upnp' => [
            'label' => 'UPnP', 'branch' => 'NetWork.Upnp',
            'fields' => [
                'enable' => ['label' => 'UPnP port mapping', 'type' => 'boolean', 'path' => ['Enable']],
                'httpPort' => ['label' => 'HTTP port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['HTTPPort']],
                'mediaPort' => ['label' => 'Media port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['MediaPort']],
                'mobilePort' => ['label' => 'Mobile port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['MobilePort']],
            ],
        ],
        'pms' => [
            'label' => 'PMS push service', 'branch' => 'NetWork.PMS',
            'fields' => [
                'enable' => ['label' => 'PMS push service', 'type' => 'boolean', 'path' => ['Enable']],
                'boxId' => ['label' => 'Box ID', 'type' => 'string', 'path' => ['BoxID']],
                'server' => ['label' => 'Server', 'type' => 'string', 'path' => ['ServName']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Port']],
                'pushInterval' => ['label' => 'Push interval (seconds)', 'type' => 'integer', 'min' => 1, 'max' => 86400, 'path' => ['PushInterval']],
            ],
        ],
        'das' => [
            'label' => 'DAS service', 'branch' => 'NetWork.DAS',
            'fields' => [
                'enable' => ['label' => 'DAS service', 'type' => 'boolean', 'path' => ['Enable']],
                'deviceId' => ['label' => 'Device ID', 'type' => 'string', 'path' => ['DeviceID']],
                'server' => ['label' => 'Server address', 'type' => 'string', 'path' => ['ServerAddr']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => ['UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => ['Password']],
            ],
        ],
    ];

    public function __construct(private DvripReader $reader)
    {
    }

    public static function list(): array
    {
        return array_map(
            static fn (string $id, array $definition): array => [
                'id' => $id,
                'name' => $definition['label'],
            ],
            array_keys(self::DEFINITIONS),
            array_values(self::DEFINITIONS)
        );
    }

    public function get(string $id): array
    {
        $definition = $this->definition($id);
        return $this->model($id, $definition, $this->readBranch($definition));
    }

    public function update(string $id, array $input): array
    {
        $definition = $this->definition($id);
        $clean = $this->validate($definition, $input);
        $current = $this->readBranch($definition);
        $updated = $current;

        foreach ($definition['fields'] as $name => $field) {
            if ($field['type'] === 'password' && $clean[$name] === '') {
                continue;
            }
            $this->setPath($updated, $field['path'], $clean[$name]);
        }

        $this->reader->setConfig($definition['branch'], $updated);
        $verified = $this->readBranch($definition);
        if ($verified !== $updated) {
            throw new DvripException(
                'write_verification_failed',
                'Configuration read-back did not match the submitted object.'
            );
        }
        return $this->model($id, $definition, $verified);
    }

    private function definition(string $id): array
    {
        if (!isset(self::DEFINITIONS[$id])) {
            throw new InvalidArgumentException('Unknown editable network service.');
        }
        return self::DEFINITIONS[$id];
    }

    private function readBranch(array $definition): array
    {
        $branch = $this->reader->getConfig($definition['branch']);
        if (!is_array($branch)) {
            throw new DvripException('device_config_missing', 'Camera returned an invalid configuration branch.');
        }
        foreach ($definition['fields'] as $field) {
            $this->getPath($branch, $field['path']);
        }
        return $branch;
    }

    private function model(string $id, array $definition, array $branch): array
    {
        $fields = [];
        $values = [];
        foreach ($definition['fields'] as $name => $field) {
            $value = $this->getPath($branch, $field['path']);
            $public = $field;
            unset($public['path']);
            if ($field['type'] === 'password') {
                $public['configured'] = is_string($value) && $value !== '';
                $values[$name] = '';
            } else {
                $values[$name] = $value;
            }
            $fields[$name] = $public;
        }
        return [
            'id' => $id,
            'label' => $definition['label'],
            'branch' => $definition['branch'],
            'fields' => $fields,
            'values' => $values,
        ];
    }

    private function validate(array $definition, array $input): array
    {
        $clean = [];
        foreach ($definition['fields'] as $name => $field) {
            if (!array_key_exists($name, $input)) {
                throw new InvalidArgumentException("Field {$name} is required.");
            }
            $value = $input[$name];
            if ($field['type'] === 'boolean') {
                if (!is_bool($value)) throw new InvalidArgumentException("Field {$name} must be boolean.");
                $clean[$name] = $value;
                continue;
            }
            if ($field['type'] === 'integer') {
                if (!is_int($value) || $value < $field['min'] || $value > $field['max']) {
                    throw new InvalidArgumentException("Field {$name} is outside its allowed range.");
                }
                $clean[$name] = $value;
                continue;
            }
            if (!is_string($value) || strlen($value) > 255) {
                throw new InvalidArgumentException("Field {$name} must be a short string.");
            }
            $clean[$name] = $field['type'] === 'password' ? $value : trim($value);
        }
        return $clean;
    }

    private function getPath(array $branch, array $path): mixed
    {
        $value = $branch;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                throw new DvripException('device_config_missing', 'Camera returned an incomplete configuration branch.');
            }
            $value = $value[$segment];
        }
        return $value;
    }

    private function setPath(array &$branch, array $path, mixed $value): void
    {
        $target = &$branch;
        foreach (array_slice($path, 0, -1) as $segment) {
            $target = &$target[$segment];
        }
        $target[$path[array_key_last($path)]] = $value;
    }
}
