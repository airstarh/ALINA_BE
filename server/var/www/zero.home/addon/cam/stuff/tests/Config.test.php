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
                'talkback' => ['speaker' => true],
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
            [['id' => 'balcon', 'name' => 'Balcon', 'talkback' => true]],
            $config->publicCameras()
        );
    } finally {
        unlink($path);
    }
});

test('config_defaults_talkback_to_unavailable_and_validates_its_shape', function (): void {
    loadConfigClass();
    $fixture = validConfigFixture();
    unset($fixture['cameras']['balcon']['talkback']);
    $path = configFixture($fixture);

    try {
        assertSameValue(false, Config::load($path)->publicCameras()[0]['talkback']);
    } finally {
        unlink($path);
    }

    foreach ([
        ['speaker' => 'yes'],
        ['speaker' => true, 'unknown' => true],
        true,
    ] as $talkback) {
        $fixture = validConfigFixture();
        $fixture['cameras']['balcon']['talkback'] = $talkback;
        $path = configFixture($fixture);
        try {
            assertThrows(static fn () => Config::load($path), UnexpectedValueException::class);
        } finally {
            unlink($path);
        }
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

test('config_accepts_per_camera_alarm_sound_labels_without_exposing_them', function (): void {
    loadConfigClass();
    $fixture = validConfigFixture();
    $fixture['cameras']['balcon']['alarm_sound'] = [
        'supported' => true, 'labels' => [524 => 'Bell Alarm'],
        'options' => [523 => 'Monitoring area warning'],
    ];
    $path = configFixture($fixture);
    try {
        $config = Config::load($path);
        assertSameValue('Bell Alarm', $config->camera('balcon')['alarm_sound']['labels'][524]);
        assertSameValue(false, str_contains(json_encode($config->publicCameras()), 'Bell Alarm'));
    } finally {
        unlink($path);
    }
});

test('config_rejects_invalid_alarm_sound_override_or_wire_code', function (): void {
    loadConfigClass();
    foreach ([
        ['supported' => 'yes'],
        ['options' => [70000 => 'Unverified sound']],
        ['labels' => [524 => '']],
    ] as $alarmSound) {
        $fixture = validConfigFixture();
        $fixture['cameras']['balcon']['alarm_sound'] = $alarmSound;
        $path = configFixture($fixture);
        try {
            assertThrows(static fn () => Config::load($path), UnexpectedValueException::class);
        } finally {
            unlink($path);
        }
    }
});

test('config_validates_private_per_service_access_overrides', function (): void {
    loadConfigClass();
    $fixture = validConfigFixture();
    $fixture['cameras']['balcon']['access'] = [
        'ports' => ['onvif' => 9000], 'credentials' => ['onvif' => ['username' => 'viewer', 'password' => 'private-value']],
    ];
    $path = configFixture($fixture);
    try {
        $config = Config::load($path);
        assertSameValue(9000, $config->camera('balcon')['access']['ports']['onvif']);
        assertTrueValue(!str_contains(json_encode($config->publicCameras()), 'private-value'));
    } finally { unlink($path); }
    foreach ([['ports' => ['onvif' => 70000]], ['ports' => ['dvrip' => 100]],
        ['credentials' => ['rtsp' => ['username' => 'viewer']]], ['ports' => 'invalid']] as $access) {
        $fixture['cameras']['balcon']['access'] = $access;
        $path = configFixture($fixture);
        try { assertThrows(static fn () => Config::load($path), UnexpectedValueException::class); }
        finally { unlink($path); }
    }
});
