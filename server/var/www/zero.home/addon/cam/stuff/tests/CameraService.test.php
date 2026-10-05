<?php

declare(strict_types=1);

use Cam\CameraService;
use Cam\DvripException;
use Cam\DvripReader;

function loadCameraServiceClasses(): void
{
    $directory = dirname(__DIR__) . '/php';
    foreach (['DvripException.php', 'DvripReader.php', 'CameraService.php'] as $file) {
        $path = $directory . '/' . $file;
        if (!is_file($path)) {
            throw new RuntimeException("Camera service class {$file} does not exist");
        }
        require_once $path;
    }
}

function fixtureCameraService(?object &$reader = null): CameraService
{
    $reader = new class implements DvripReader {
        public array $calls = [];
        public array $configValues = [];
        public array $configErrors = [];
        public array $systemInfo = [];
        public array $systemFunction = [];

        public function getConfig(string $command): mixed
        {
            $this->calls[] = $command;
            if (isset($this->configErrors[$command])) {
                throw $this->configErrors[$command];
            }
            return $this->configValues[$command] ?? [];
        }

        public function getSystemInfo(): array
        {
            $this->calls[] = 'SystemInfo';
            return $this->systemInfo;
        }

        public function getSystemFunction(): array
        {
            $this->calls[] = 'SystemFunction';
            return $this->systemFunction;
        }

        public function close(): void
        {
        }
    };
    return new CameraService($reader, 'balcon', 'Balcon');
}

test('camera_service_lists_exact_safe_sections', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService();
    assertSameValue(
        [
            'general', 'network', 'camera', 'detection', 'encoding',
            'advanced_encoding', 'recording', 'storage', 'alarms', 'uart',
            'video_ui', 'capabilities', 'system', 'ability',
        ],
        array_column($service->sections(), 'id')
    );
});

test('camera_service_rejects_arbitrary_section', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    assertThrows(
        static fn () => $service->config('NetWork.NetEmail'),
        InvalidArgumentException::class
    );
    assertSameValue([], $reader->calls);
});

test('camera_service_maps_section_to_verified_getter', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['NetWork'] = ['NetDNS' => ['Address' => '192.0.2.53']];
    assertSameValue(
        ['NetDNS' => ['Address' => '192.0.2.53']],
        $service->config('network')
    );
    assertSameValue(['NetWork'], $reader->calls);
});

test('camera_service_sanitizes_status_and_nested_secrets', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->systemInfo = [
        'SoftWareVersion' => 'fixture-version',
        'SerialNo' => 'fixture-serial',
        'Password' => 'hidden',
        'Nested' => [
            'passwd' => 'hidden',
            'Pwd' => 'hidden',
            'AccessToken' => 'hidden',
            'ClientSecret' => 'hidden',
            'WifiKey' => 'hidden',
            'UserName' => 'operator',
            'ServerAddr' => '192.0.2.25',
            'Email' => 'operator@example.test',
            'StreamURI' => 'rtsp://operator:hidden@192.0.2.25/live',
            'Address' => 'rtsp://operator:hidden@192.0.2.25/live',
            'Items' => ['rtsp://operator:hidden@192.0.2.25/live'],
            'StreamURL' => 'http://192.0.2.25/live?password=hidden&token=also-hidden&quality=3',
            'ApiKey' => 'hidden',
        ],
    ];

    $status = $service->status();
    assertSameValue('balcon', $status['camera']['id']);
    assertSameValue('Balcon', $status['camera']['name']);
    assertSameValue('[redacted]', $status['system']['Password']);
    assertSameValue('[redacted]', $status['system']['Nested']['passwd']);
    assertSameValue('[redacted]', $status['system']['Nested']['Pwd']);
    assertSameValue('[redacted]', $status['system']['Nested']['AccessToken']);
    assertSameValue('[redacted]', $status['system']['Nested']['ClientSecret']);
    assertSameValue('[redacted]', $status['system']['Nested']['WifiKey']);
    assertSameValue('operator', $status['system']['Nested']['UserName']);
    assertSameValue('192.0.2.25', $status['system']['Nested']['ServerAddr']);
    assertSameValue('operator@example.test', $status['system']['Nested']['Email']);
    assertSameValue(
        'rtsp://[redacted]@192.0.2.25/live',
        $status['system']['Nested']['StreamURI']
    );
    assertSameValue('rtsp://[redacted]@192.0.2.25/live', $status['system']['Nested']['Address']);
    assertSameValue('rtsp://[redacted]@192.0.2.25/live', $status['system']['Nested']['Items'][0]);
    assertSameValue(
        'http://192.0.2.25/live?password=%5Bredacted%5D&token=%5Bredacted%5D&quality=3',
        $status['system']['Nested']['StreamURL']
    );
    assertSameValue('[redacted]', $status['system']['Nested']['ApiKey']);
    assertSameValue('fixture-serial', $status['system']['SerialNo']);
});

test('camera_service_preserves_lists_and_scalar_types', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['General'] = [
        'Enabled' => true,
        'Count' => 3,
        'Ratio' => 1.5,
        'Value' => null,
        'Items' => ['one', 2, false],
    ];
    assertSameValue($reader->configValues['General'], $service->config('general'));
});

test('camera_service_all_isolates_unsupported_and_timeout_sections', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['General'] = ['Language' => 'English'];
    $reader->configErrors['NetWork'] = new DvripException('device_config_missing', 'missing');
    $reader->configErrors['Camera'] = new DvripException('transport_timeout', 'timeout');

    $all = $service->all();
    assertSameValue(true, $all['general']['ok']);
    assertSameValue(['Language' => 'English'], $all['general']['data']);
    assertSameValue(false, $all['network']['ok']);
    assertSameValue('device_config_missing', $all['network']['error']['code']);
    assertSameValue(false, $all['camera']['ok']);
    assertSameValue('transport_timeout', $all['camera']['error']['code']);
    assertSameValue(true, $all['detection']['ok']);
});
