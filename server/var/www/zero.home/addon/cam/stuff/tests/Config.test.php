<?php

declare(strict_types=1);

use Cam\Config;

function loadConfigClass(): void
{
    $path = dirname(__DIR__) . '/php/Config.php';
    if (!is_file($path)) {
        throw new RuntimeException('Cam\\Config does not exist');
    }

    require_once $path;
}

function configFixture(array $config): string
{
    $path = tempnam(sys_get_temp_dir(), 'cam-config-');
    if ($path === false) {
        throw new RuntimeException('could not create configuration fixture');
    }

    $php = "<?php\nreturn " . var_export($config, true) . ";\n";
    if (file_put_contents($path, $php) === false) {
        throw new RuntimeException('could not write configuration fixture');
    }

    return $path;
}

function validConfigFixture(): array
{
    return [
        'default' => 'balcon',
        'cameras' => [
            'balcon' => [
                'name' => 'Balcon',
                'host' => '192.0.2.10',
                'dvrip' => [
                    'port' => 34567,
                    'username' => 'fixture-user',
                    'password' => 'fixture-secret',
                    'timeout' => 2.5,
                ],
            ],
        ],
    ];
}

test('config_returns_only_public_camera_metadata', function (): void {
    loadConfigClass();
    $path = configFixture(validConfigFixture());

    try {
        $config = Config::load($path);
        assertSameValue(
            [['id' => 'balcon', 'name' => 'Balcon']],
            $config->publicCameras()
        );
    } finally {
        unlink($path);
    }
});

test('config_selects_balcon_by_default', function (): void {
    loadConfigClass();
    $path = configFixture(validConfigFixture());

    try {
        assertSameValue('balcon', Config::load($path)->defaultCameraId());
    } finally {
        unlink($path);
    }
});

test('config_rejects_unknown_camera', function (): void {
    loadConfigClass();
    $path = configFixture(validConfigFixture());

    try {
        assertThrows(
            static fn () => Config::load($path)->camera('door'),
            InvalidArgumentException::class
        );
    } finally {
        unlink($path);
    }
});

test('config_rejects_missing_required_dvrip_keys', function (): void {
    loadConfigClass();
    $fixture = validConfigFixture();
    unset($fixture['cameras']['balcon']['dvrip']['password']);
    $path = configFixture($fixture);

    try {
        assertThrows(static fn () => Config::load($path), UnexpectedValueException::class);
    } finally {
        unlink($path);
    }
});

test('config_does_not_expose_private_values', function (): void {
    loadConfigClass();
    $path = configFixture(validConfigFixture());

    try {
        $config = Config::load($path);
        $publicJson = json_encode($config->publicCameras(), JSON_THROW_ON_ERROR);
        assertTrueValue(!str_contains($publicJson, '192.0.2.10'));
        assertTrueValue(!str_contains($publicJson, 'fixture-user'));
        assertTrueValue(!str_contains($publicJson, 'fixture-secret'));
        assertSameValue('fixture-secret', $config->camera('balcon')['dvrip']['password']);
    } finally {
        unlink($path);
    }
});

test('config_accepts_missing_optional_timeout', function (): void {
    loadConfigClass();
    $fixture = validConfigFixture();
    unset($fixture['cameras']['balcon']['dvrip']['timeout']);
    $path = configFixture($fixture);

    try {
        assertSameValue('balcon', Config::load($path)->defaultCameraId());
    } finally {
        unlink($path);
    }
});
