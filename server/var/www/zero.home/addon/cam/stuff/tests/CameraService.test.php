<?php

declare(strict_types=1);

use Cam\CameraService;
use Cam\DvripException;
use Cam\DvripReader;

function loadCameraServiceClasses(): void
{
    $directory = dirname(__DIR__) . '/php';
    foreach (['DvripException.php', 'DvripReader.php', 'ExtendedEditorDefinitions.php', 'NetworkServiceEditor.php', 'CameraService.php'] as $file) {
        $path = $directory . '/' . $file;
        if (!is_file($path)) {
            throw new RuntimeException("Camera service class {$file} does not exist");
        }
        require_once $path;
    }
}

function fixtureCameraService(?object &$reader = null, array $cameraSettings = []): CameraService
{
    $reader = new class implements DvripReader {
        public array $calls = [];
        public array $configValues = [];
        public array $configErrors = [];
        public array $systemInfo = [];
        public array $systemFunction = [];
        public array $infoValues = [];
        public bool $persistWrites = true;
        public array $testMailCalls = [];
        public array $testFtpCalls = [];
        public bool $ftpTestAccepted = true;
        public array $ptzPresetCalls = [];

        public function getConfig(string $command): mixed
        {
            $this->calls[] = $command;
            if (isset($this->configErrors[$command])) {
                throw $this->configErrors[$command];
            }
            return $this->configValues[$command] ?? [];
        }

        public function setConfig(string $command, mixed $value): bool
        {
            $this->calls[] = ['set', $command, $value];
            if ($this->persistWrites) {
                $this->configValues[$command] = $value;
            }
            return true;
        }

        public function getSystemInfo(): array
        {
            $this->calls[] = 'SystemInfo';
            return $this->systemInfo;
        }

        public function getInfo(string $command): mixed
        {
            $this->calls[] = ['info', $command];
            return $this->infoValues[$command] ?? null;
        }

        public function getSystemFunction(): array
        {
            $this->calls[] = 'SystemFunction';
            return $this->systemFunction;
        }

        public function testMail(array $settings): bool
        {
            $this->calls[] = 'OPMailTest';
            $this->testMailCalls[] = $settings;
            return true;
        }

        public function testFtp(array $settings): bool
        {
            $this->calls[] = 'OPFTPTest';
            $this->testFtpCalls[] = $settings;
            return $this->ftpTestAccepted;
        }

        public function reboot(): bool
        {
            $this->calls[] = 'Reboot';
            return true;
        }

        public function ptzPreset(string $operation, int $preset): bool
        {
            $this->ptzPresetCalls[] = [$operation, $preset];
            return true;
        }

        public function close(): void
        {
        }
    };
    return new CameraService($reader, 'balcon', 'Balcon', $cameraSettings);
}

test('camera_service_exposes_only_live_advertised_ptz_controls', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    assertSameValue(false, $service->ptzCapabilities()['available']);

    $reader->systemFunction = ['OtherFunction' => [
        'SupportPTZDirectionControl' => true,
        'SupportSetPTZPresetAttribute' => true,
    ]];
    $capabilities = $service->ptzCapabilities();
    assertSameValue(true, $capabilities['available']);
    assertSameValue(true, $capabilities['presets']);
    assertSameValue(['low', 'medium', 'high'], $capabilities['speeds']);
    assertSameValue(['up', 'up-right', 'right', 'down-right', 'down', 'down-left', 'left', 'up-left'], $capabilities['directions']);
});

test('camera_service_validates_and_dispatches_ptz_movement_and_presets', function (): void {
    loadCameraServiceClasses();
    $pulses = [];
    $service = fixtureCameraService($reader, ['host' => '192.0.2.10', 'dvrip' => [
        'port' => 34567, 'username' => 'admin', 'password' => 'secret',
    ]]);
    $reader->systemFunction = ['OtherFunction' => [
        'SupportPTZDirectionControl' => true,
        'SupportSetPTZPresetAttribute' => true,
    ]];
    $service = new CameraService($reader, 'balcon', 'Balcon', [
        'host' => '192.0.2.10', 'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => 'secret'],
    ], null, static function (string $direction, string $speed, int $durationMs) use (&$pulses): void {
        $pulses[] = [$direction, $speed, $durationMs];
    });

    assertSameValue(['accepted' => true], $service->ptzCommand([
        'operation' => 'move', 'direction' => 'left', 'speed' => 'medium', 'durationMs' => 180,
    ]));
    assertSameValue([['left', 'medium', 180]], $pulses);
    assertSameValue(['accepted' => true], $service->ptzCommand(['operation' => 'goto', 'preset' => 5]));
    assertSameValue([['goto', 5]], $reader->ptzPresetCalls);

    assertThrows(static fn () => $service->ptzCommand([
        'operation' => 'move', 'direction' => 'spin', 'speed' => 'medium', 'durationMs' => 180,
    ]), InvalidArgumentException::class);
    assertThrows(static fn () => $service->ptzCommand(['operation' => 'goto', 'preset' => 0]), InvalidArgumentException::class);
    assertSameValue(1, count($pulses));
    assertSameValue(1, count($reader->ptzPresetCalls));
});

test('camera_service_rejects_ptz_commands_when_motor_capability_is_absent', function (): void {
    loadCameraServiceClasses();
    $called = false;
    $service = fixtureCameraService($reader);
    $service = new CameraService($reader, 'door', 'Door', [], null,
        static function () use (&$called): void { $called = true; });
    assertThrows(static fn () => $service->ptzCommand([
        'operation' => 'move', 'direction' => 'left', 'speed' => 'low', 'durationMs' => 180,
    ]), InvalidArgumentException::class);
    assertSameValue(false, $called);
});

test('camera_service_passes_per_camera_sound_fallback_to_human_detection_editor', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader, ['alarm_sound' => [
        'supported' => true, 'options' => [524 => 'Bell Alarm'],
    ]]);
    $reader->configValues['Detect.HumanDetection'] = [[
        'Enable' => true, 'PushInterval' => 3000, 'ShowRule' => 0, 'ShowTrack' => 0,
        'PedRule' => [['Enable' => true, 'RuleType' => 1]],
    ]];
    $reader->configValues['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => true, 'VoiceType' => 524,
    ]];
    $model = $service->editor('human_detection');
    assertSameValue('Bell Alarm', $model['fields']['soundType']['optionLabels']['524']);
});

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

test('camera_service_reboots_camera', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    assertSameValue(['accepted' => true], $service->reboot());
    assertSameValue(['Reboot'], $reader->calls);
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

test('camera_service_returns_sanitized_wifi_access_points', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->infoValues['WifiAP'] = [
        'Numbers' => 2,
        'WifiAP' => [
            ['SSID' => 'q001', 'Auth' => 'WPA2PSK', 'EncrypType' => 'AES', 'Channel' => 8, 'NetType' => 'Infra', 'RSSI' => 'VeryGood'],
            ['SSID' => '', 'Auth' => 'WPA2PSK', 'EncrypType' => 'AES', 'Channel' => 9, 'NetType' => 'Infra', 'RSSI' => 'Good'],
        ],
    ];

    $result = $service->wifiAccessPoints();
    assertSameValue(2, $result['count']);
    assertSameValue('q001', $result['accessPoints'][0]['ssid']);
    assertSameValue('WPA2PSK', $result['accessPoints'][0]['authentication']);
    assertSameValue('', $result['accessPoints'][1]['ssid']);
    assertSameValue([['info', 'WifiAP']], $reader->calls);
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
            'Keys' => 'hidden-wifi-key',
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
    assertSameValue('[redacted]', $status['system']['Nested']['Keys']);
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

test('camera_service_returns_current_smtp_password_in_editable_model', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['NetWork.NetEmail'] = smtpFixture();

    assertSameValue([
        'enable' => false,
        'server' => 'smtp.example.test',
        'port' => 25,
        'username' => 'camera@example.test',
        'password' => 'keep-me',
        'useSsl' => 0,
        'sendAddress' => 'camera@example.test',
        'recipients' => ['alerts@example.test', 'none', 'none', 'none', 'none'],
        'title' => 'Camera alert',
    ], $service->smtp());
});

test('camera_service_tests_current_smtp_settings_without_saving_them', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['NetWork.NetEmail'] = smtpFixture();
    $reader->systemFunction = ['OtherFunction' => ['SupportMailTest' => true]];

    assertSameValue(['accepted' => true], $service->testSmtp());
    assertSameValue(['SystemFunction', 'NetWork.NetEmail', 'OPMailTest'], $reader->calls);
    assertSameValue([[
        'Enable' => false,
        'MailServer' => smtpFixture()['MailServer'],
        'UseSSL' => 0,
        'SendAddr' => 'camera@example.test',
        'Recievers' => ['alerts@example.test', 'none', 'none', 'none', 'none'],
        'Schedule' => ['schedule-fixture'],
        'Title' => 'Camera alert',
    ]], $reader->testMailCalls);
    assertSameValue([], array_filter($reader->calls, 'is_array'));
});

test('camera_service_refuses_smtp_test_when_camera_does_not_advertise_support', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->systemFunction = ['OtherFunction' => ['SupportMailTest' => false]];

    $exception = assertThrows(static fn () => $service->testSmtp(), DvripException::class);
    assertSameValue('device_unsupported', $exception->publicCode());
    assertSameValue(['SystemFunction'], $reader->calls);
});

test('camera_service_tests_saved_ftp_settings_without_saving_them', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $settings = [
        'Directory' => '/camera', 'Enable' => true, 'MaxFileLen' => 128,
        'Server' => [
            'Address' => '0x00000000', 'Anonymity' => false,
            'Name' => 'ftp.example.test', 'Password' => 'fixture-secret',
            'Port' => 21, 'UserName' => 'camera',
        ],
    ];
    $reader->configValues['NetWork.NetFTP'] = $settings;
    $reader->systemFunction = ['OtherFunction' => ['SupportFTPTest' => true]];

    assertSameValue(['accepted' => true], $service->testFtp());
    assertSameValue(['SystemFunction', 'NetWork.NetFTP', 'OPFTPTest'], $reader->calls);
    assertSameValue([$settings], $reader->testFtpCalls);
});

test('camera_service_refuses_ftp_test_without_advertised_support', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->systemFunction = ['OtherFunction' => ['SupportFTPTest' => false]];

    $exception = assertThrows(static fn () => $service->testFtp(), DvripException::class);
    assertSameValue('device_unsupported', $exception->publicCode());
    assertSameValue(['SystemFunction'], $reader->calls);
});

test('camera_service_reports_rejected_ftp_test_distinctly', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->systemFunction = ['OtherFunction' => ['SupportFTPTest' => true]];
    $reader->configValues['NetWork.NetFTP'] = [
        'Directory' => '/camera', 'Enable' => true, 'MaxFileLen' => 128,
        'Server' => [
            'Address' => '0x00000000', 'Anonymity' => false,
            'Name' => 'ftp.example.test', 'Password' => 'fixture-secret',
            'Port' => 21, 'UserName' => 'camera',
        ],
    ];
    $reader->ftpTestAccepted = false;

    $exception = assertThrows(static fn () => $service->testFtp(), DvripException::class);
    assertSameValue('ftp_test_failed', $exception->publicCode());
});

test('camera_service_smtp_update_clears_empty_password_and_preserves_unedited_fields', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['NetWork.NetEmail'] = smtpFixture();

    $result = $service->updateSmtp([
        'enable' => true,
        'server' => 'mail.example.test',
        'port' => 587,
        'username' => 'mailer',
        'password' => '',
        'useSsl' => 1,
        'sendAddress' => 'camera@example.test',
        'recipients' => ['one@example.test', 'two@example.test'],
        'title' => 'Balcon alert',
    ]);

    $write = $reader->calls[1];
    assertSameValue('set', $write[0]);
    assertSameValue('NetWork.NetEmail', $write[1]);
    assertSameValue('', $write[2]['MailServer']['Password']);
    assertSameValue('0x00000000', $write[2]['MailServer']['Address']);
    assertSameValue(['schedule-fixture'], $write[2]['Schedule']);
    assertSameValue(['one@example.test', 'two@example.test', 'none', 'none', 'none'], $write[2]['Recievers']);
    assertSameValue(true, $result['enable']);
    assertSameValue(587, $result['port']);
    assertSameValue('', $result['password']);
    assertSameValue(['NetWork.NetEmail', $reader->calls[1], 'NetWork.NetEmail'], $reader->calls);
});

test('camera_service_smtp_update_rejects_invalid_input_before_write', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['NetWork.NetEmail'] = smtpFixture();

    assertThrows(static fn () => $service->updateSmtp([
        'enable' => true,
        'server' => '',
        'port' => 70000,
        'username' => '',
        'password' => '',
        'useSsl' => 0,
        'sendAddress' => '',
        'recipients' => [],
        'title' => '',
    ]), InvalidArgumentException::class);
    assertSameValue([], $reader->calls);
});

test('camera_service_smtp_update_rejects_readback_mismatch', function (): void {
    loadCameraServiceClasses();
    $service = fixtureCameraService($reader);
    $reader->configValues['NetWork.NetEmail'] = smtpFixture();
    $reader->persistWrites = false;

    $exception = assertThrows(static fn () => $service->updateSmtp([
        'enable' => false,
        'server' => 'other.example.test',
        'port' => 25,
        'username' => 'camera@example.test',
        'password' => '',
        'useSsl' => 0,
        'sendAddress' => 'camera@example.test',
        'recipients' => ['alerts@example.test'],
        'title' => 'Camera alert',
    ]), DvripException::class);
    assertSameValue('write_verification_failed', $exception->publicCode());
});

function smtpFixture(): array
{
    return [
        'Enable' => false,
        'MailServer' => [
            'Address' => '0x00000000',
            'Anonymity' => false,
            'Name' => 'smtp.example.test',
            'Password' => 'keep-me',
            'Port' => 25,
            'UserName' => 'camera@example.test',
        ],
        'Recievers' => ['alerts@example.test', 'none', 'none', 'none', 'none'],
        'Schedule' => ['schedule-fixture'],
        'SendAddr' => 'camera@example.test',
        'Title' => 'Camera alert',
        'UseSSL' => 0,
    ];
}
