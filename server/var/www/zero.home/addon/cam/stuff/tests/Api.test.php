<?php

declare(strict_types=1);

use Cam\Api;
use Cam\CameraService;
use Cam\Config;
use Cam\DvripException;
use Cam\DvripReader;

function loadApiClasses(): void
{
    $directory = dirname(__DIR__) . '/php';
    foreach (['Config.php', 'DvripException.php', 'DvripReader.php', 'CameraService.php', 'api.php'] as $file) {
        $path = $directory . '/' . $file;
        if (!is_file($path)) {
            throw new RuntimeException("API class {$file} does not exist");
        }
        require_once $path;
    }
}

function apiConfigFixture(): string
{
    $path = tempnam(sys_get_temp_dir(), 'cam-api-config-');
    if ($path === false) {
        throw new RuntimeException('could not create API config fixture');
    }
    $config = [
        'default' => 'balcon',
        'cameras' => [
            'balcon' => [
                'name' => 'Balcon',
                'host' => '192.0.2.10',
                'dvrip' => [
                    'port' => 34567,
                    'username' => 'fixture-user',
                    'password' => 'fixture-secret',
                ],
            ],
        ],
    ];
    file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";\n");
    return $path;
}

function fixtureApi(?int &$factoryCalls = null, ?object &$reader = null): array
{
    loadApiClasses();
    $path = apiConfigFixture();
    $factoryCalls = 0;
    $reader = new class implements DvripReader {
        public bool $closed = false;

        public function getConfig(string $command): mixed
        {
            return ['Command' => $command, 'Password' => 'fixture-secret'];
        }

        public function getSystemInfo(): array
        {
            return ['SoftWareVersion' => 'fixture-version', 'Password' => 'fixture-secret'];
        }

        public function getSystemFunction(): array
        {
            return ['NetServerFunction' => []];
        }

        public function close(): void
        {
            $this->closed = true;
        }
    };

    $api = new Api(
        Config::load($path),
        static function (string $id, array $camera) use (&$factoryCalls, $reader): CameraService {
            $factoryCalls++;
            return new CameraService($reader, $id, $camera['name']);
        }
    );
    return [$api, $path];
}

test('api_lists_public_cameras_without_connecting', function (): void {
    [$api, $path] = fixtureApi($calls);
    try {
        $response = $api->handle(['action' => 'cameras']);
        assertSameValue(200, $response['status']);
        assertSameValue([
            'ok' => true,
            'data' => [
                'cameras' => [['id' => 'balcon', 'name' => 'Balcon']],
                'default' => 'balcon',
            ],
            'error' => null,
        ], $response['body']);
        assertSameValue(0, $calls);
    } finally {
        unlink($path);
    }
});

test('api_requires_camera_and_section_parameters', function (): void {
    [$api, $path] = fixtureApi();
    try {
        assertSameValue(400, $api->handle(['action' => 'status'])['status']);
        assertSameValue(400, $api->handle(['action' => 'config', 'camera' => 'balcon'])['status']);
    } finally {
        unlink($path);
    }
});

test('api_rejects_unknown_action_camera_and_section', function (): void {
    [$api, $path] = fixtureApi();
    try {
        assertSameValue(400, $api->handle(['action' => 'delete'])['status']);
        assertSameValue(404, $api->handle(['action' => 'status', 'camera' => 'door'])['status']);
        assertSameValue(400, $api->handle([
            'action' => 'config', 'camera' => 'balcon', 'section' => 'NetWork.NetEmail',
        ])['status']);
    } finally {
        unlink($path);
    }
});

test('api_dispatches_status_sections_single_and_all_config', function (): void {
    [$api, $path] = fixtureApi($calls, $reader);
    try {
        $status = $api->handle(['action' => 'status', 'camera' => 'balcon']);
        $sections = $api->handle(['action' => 'sections', 'camera' => 'balcon']);
        $single = $api->handle([
            'action' => 'config', 'camera' => 'balcon', 'section' => 'general',
        ]);
        $all = $api->handle([
            'action' => 'config', 'camera' => 'balcon', 'section' => 'all',
        ]);

        assertSameValue('fixture-version', $status['body']['data']['system']['SoftWareVersion']);
        assertSameValue(14, count($sections['body']['data']));
        assertSameValue('General', $single['body']['data']['Command']);
        assertSameValue(true, $all['body']['data']['general']['ok']);
        assertSameValue(3, $calls);
        assertTrueValue($reader->closed, 'reader must close after each API operation');
    } finally {
        unlink($path);
    }
});

test('api_lists_static_sections_without_connecting_to_camera', function (): void {
    [$api, $path] = fixtureApi($calls);
    try {
        $response = $api->handle(['action' => 'sections', 'camera' => 'balcon']);
        assertSameValue(200, $response['status']);
        assertSameValue(14, count($response['body']['data']));
        assertSameValue(0, $calls);
    } finally {
        unlink($path);
    }
});

test('camera_request_lock_is_exclusive_for_same_camera', function (): void {
    loadApiClasses();
    $directory = sys_get_temp_dir() . '/cam-lock-test-' . bin2hex(random_bytes(5));
    mkdir($directory);
    $lockPath = null;
    try {
        Cam\withCameraRequestLock('balcon', function (string $path) use (&$lockPath): void {
            $lockPath = $path;
            assertTrueValue(is_dir($path), 'lock directory must exist while held');
            assertSameValue(false, @mkdir($path));
        }, $directory);
        assertSameValue(false, is_dir($lockPath));
    } finally {
        rmdir($directory);
    }
});

test('api_emits_json_and_no_cache_headers', function (): void {
    [$api, $path] = fixtureApi();
    try {
        $headers = $api->handle(['action' => 'cameras'])['headers'];
        assertSameValue('application/json; charset=utf-8', $headers['Content-Type']);
        assertSameValue('no-store, max-age=0', $headers['Cache-Control']);
    } finally {
        unlink($path);
    }
});

test('api_encoded_response_contains_no_private_credentials', function (): void {
    [$api, $path] = fixtureApi();
    try {
        foreach ([
            ['action' => 'cameras'],
            ['action' => 'status', 'camera' => 'balcon'],
            ['action' => 'config', 'camera' => 'balcon', 'section' => 'general'],
        ] as $query) {
            $json = json_encode($api->handle($query)['body'], JSON_THROW_ON_ERROR);
            assertTrueValue(!str_contains($json, 'fixture-secret'));
            assertTrueValue(!str_contains($json, 'fixture-user'));
            assertTrueValue(!str_contains($json, '192.0.2.10'));
        }
    } finally {
        unlink($path);
    }
});

test('api_maps_transport_timeout_to_gateway_timeout', function (): void {
    loadApiClasses();
    $path = apiConfigFixture();
    try {
        $api = new Api(
            Config::load($path),
            static function (): CameraService {
                throw new DvripException('transport_timeout', 'timed out');
            }
        );
        $response = $api->handle(['action' => 'status', 'camera' => 'balcon']);
        assertSameValue(504, $response['status']);
        assertSameValue('transport_timeout', $response['body']['error']['code']);
    } finally {
        unlink($path);
    }
});

test('production_api_wraps_missing_private_config_in_json_envelope', function (): void {
    loadApiClasses();
    $response = Cam\runProductionApi(['action' => 'cameras'], '/definitely/missing/config.php');
    assertSameValue(500, $response['status']);
    assertSameValue(false, $response['body']['ok']);
    assertSameValue('config_error', $response['body']['error']['code']);
    assertTrueValue(!str_contains(json_encode($response['body']), '/definitely/missing'));
});

test('production_api_wraps_invalid_private_config_in_json_envelope', function (): void {
    loadApiClasses();
    $path = tempnam(sys_get_temp_dir(), 'cam-invalid-config-');
    file_put_contents($path, "<?php return ['broken' => true];");
    try {
        $response = Cam\runProductionApi(['action' => 'cameras'], $path);
        assertSameValue(500, $response['status']);
        assertSameValue('config_error', $response['body']['error']['code']);
    } finally {
        unlink($path);
    }
});

test('production_api_uses_config_from_camera_app_directory', function (): void {
    loadApiClasses();
    $expected = dirname(__DIR__, 2) . '/config.php';
    assertSameValue($expected, Cam\resolveProductionConfigPath());
});
