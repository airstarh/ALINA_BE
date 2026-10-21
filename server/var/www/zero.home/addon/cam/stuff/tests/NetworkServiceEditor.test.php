<?php

declare(strict_types=1);

use Cam\DvripException;
use Cam\DvripReader;
use Cam\NetworkServiceEditor;

require_once dirname(__DIR__) . '/php/DvripException.php';
require_once dirname(__DIR__) . '/php/DvripReader.php';

function loadNetworkServiceEditorClass(): void
{
    require_once dirname(__DIR__) . '/php/ExtendedEditorDefinitions.php';
    require_once dirname(__DIR__) . '/php/NetworkServiceEditor.php';
}

function networkEditorFixture(?object &$reader = null, array $alarmSound = []): NetworkServiceEditor
{
    loadNetworkServiceEditorClass();
    $reader = new class implements DvripReader {
        public array $values = [];
        public array $errors = [];
        public array $calls = [];
        public bool $persistWrites = true;
        public bool $normalizeBalconWifiWrites = false;
        public array $systemFunction = [];
        public array $encodeCapability = [];

        public function getConfig(string $command): mixed
        {
            $this->calls[] = ['get', $command];
            if (isset($this->errors[$command])) {
                throw $this->errors[$command];
            }
            return $this->values[$command] ?? null;
        }

        public function setConfig(string $command, mixed $value): bool
        {
            $this->calls[] = ['set', $command, $value];
            if ($this->persistWrites) {
                if ($command === 'NetWork.Wifi' && $this->normalizeBalconWifiWrites) {
                    $value['Auth'] = $value['Auth'] === 'WPAPSK' ? "\x01" : $value['Auth'];
                    $value['EncrypType'] = $value['EncrypType'] === 'TKIP' ? "\x06" : $value['EncrypType'];
                }
                $this->values[$command] = $value;
            }
            return true;
        }

        public function getSystemInfo(): array { return []; }
        public function getInfo(string $command): mixed { return null; }
        public function getSystemFunction(): array { return $this->systemFunction; }
        public function getEncodeCapability(): array { return $this->encodeCapability; }
        public function testMail(array $settings): bool { return true; }
        public function testFtp(array $settings): bool { return true; }
        public function reboot(): bool { return true; }
        public function close(): void {}
    };
    return new NetworkServiceEditor($reader, $alarmSound);
}

test('network_editor_lists_exact_sixty_nine_allowlisted_forms', function (): void {
    $editor = networkEditorFixture();
    assertSameValue(
        [
            'ftp', 'ntp', 'upnp', 'pms', 'das', 'multicast', 'pppoe', 'rtsp', 'serial_bridge', 'nat',
            'wired_network', 'dhcp', 'wifi', 'wifi_signal', 'vendor_connections', 'online_upgrade',
            'ddns_1', 'ddns_2', 'ddns_3', 'ddns_4', 'ddns_5',
            'general_preferences', 'locale_formats', 'defog', 'white_light', 'overlay_indicators',
            'osd_logo', 'output_volume', 'input_volume', 'detection_tracking', 'human_detection',
            'auto_maintain', 'ptz_resume', 'network_watchdog', 'ip_adaptive', 'network_order',
            'alarm_server_1', 'alarm_server_2', 'alarm_server_3', 'alarm_server_4', 'alarm_server_5',
            'distortion', 'fisheye', 'image_basic', 'image_advanced',
            'motion_detection', 'blind_detection', 'video_loss_detection', 'vehicle_detection',
            'main_stream', 'extra_stream', 'snapshot_image', 'smart_h264', 'video_color_day', 'video_color_night', 'video_widget',
            'snapshot', 'recording', 'storage_failure', 'storage_low_space', 'storage_missing',
            'alarm_output', 'local_alarm', 'network_abort_alarm', 'network_alarm', 'ip_conflict_alarm',
            'uart_comm_1', 'ptz_serial', 'rs485',
        ],
        array_column($editor->list(), 'id')
    );
    assertThrows(static fn () => $editor->get('netcommon'), InvalidArgumentException::class);
});

test('snapshot_image_editor_updates_three_profiles_and_preserves_unowned_encoding_fields', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['AVEnc.Encode'] = snapshotImageBranchFixture();
    foreach ([0, 1, 2] as $index) {
        $reader->values['AVEnc.Encode'][0]['SnapFormat'][$index]['Video']['Resolution'] = '1080P';
        $reader->values['AVEnc.Encode'][0]['SnapFormat'][$index]['Video']['BitRate'] = 1024;
    }
    $reader->values['AVEnc'] = ['DefinitionCfgTable' => ['Table' => [
        'D1' => ['BitrateMap' => [96, 192, 256, 384, 448, 512]],
    ]]];
    $reader->encodeCapability = [
        'EncodeInfo' => [
            ['StreamType' => 'MainStream', 'ResolutionMask' => '0x00004001'],
            ['StreamType' => 'ExtraStream2', 'ResolutionMask' => '0x00000002'],
            ['StreamType' => 'JPEGStream', 'ResolutionMask' => '0x0000004B'],
        ],
    ];

    $model = $editor->get('snapshot_image');
    assertSameValue('Snapshot image', $model['label']);
    assertSameValue('Regular snapshot', $model['fields']['regularResolution']['group']);
    assertSameValue('Motion-detection snapshot', $model['fields']['motionResolution']['group']);
    assertSameValue('Alarm snapshot', $model['fields']['alarmResolution']['group']);
    assertSameValue('D1', $model['values']['regularResolution']);
    assertSameValue(['D1', 'HD1', 'CIF', 'QVGA'], $model['fields']['regularResolution']['options']);
    assertSameValue(512, $model['values']['regularBitRate']);
    assertSameValue([96, 192, 256, 384, 448, 512], $model['fields']['regularBitRate']['options']);
    assertSameValue([1, 2, 3, 4, 5, 6], $model['fields']['regularQuality']['options']);

    $saved = $editor->update('snapshot_image', [
        'regularEnabled' => true,
        'regularResolution' => 'D1',
        'regularQuality' => 6,
        'regularBitRate' => 512,
        'regularBitRateControl' => 'VBR',
        'motionEnabled' => false,
        'motionResolution' => 'D1',
        'motionQuality' => 6,
        'motionBitRate' => 512,
        'motionBitRateControl' => 'VBR',
        'alarmEnabled' => true,
        'alarmResolution' => 'D1',
        'alarmQuality' => 6,
        'alarmBitRate' => 512,
        'alarmBitRateControl' => 'VBR',
    ]);

    $written = array_values(array_filter($reader->calls, static fn (array $call): bool => $call[0] === 'set'))[0];
    assertSameValue('AVEnc.Encode', $written[1]);
    assertSameValue('D1', $written[2][0]['SnapFormat'][0]['Video']['Resolution']);
    assertSameValue(512, $written[2][0]['SnapFormat'][0]['Video']['BitRate']);
    assertSameValue(false, $written[2][0]['SnapFormat'][1]['VideoEnable']);
    assertSameValue(true, $written[2][0]['SnapFormat'][2]['VideoEnable']);
    assertSameValue('H.264', $written[2][0]['SnapFormat'][0]['Video']['Compression']);
    assertSameValue(1, $written[2][0]['SnapFormat'][0]['Video']['FPS']);
    assertSameValue('keep-profile', $written[2][0]['SnapFormat'][0]['VendorPrivate']);
    assertSameValue('keep-encode', $written[2][0]['VendorPrivate']);
    assertSameValue(6, $saved['values']['alarmQuality']);
});

test('snapshot_image_editor_rejects_quality_outside_documented_range', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['AVEnc.Encode'] = snapshotImageBranchFixture();
    $input = [];
    foreach (['regular', 'motion', 'alarm'] as $profile) {
        $input[$profile . 'Enabled'] = true;
        $input[$profile . 'Resolution'] = 'D1';
        $input[$profile . 'Quality'] = $profile === 'alarm' ? 7 : 4;
        $input[$profile . 'BitRate'] = 512;
        $input[$profile . 'BitRateControl'] = 'VBR';
    }

    assertThrows(static fn () => $editor->update('snapshot_image', $input), InvalidArgumentException::class);
    assertSameValue([], array_values(array_filter($reader->calls, static fn (array $call): bool => $call[0] === 'set')));
});

test('network_editor_combines_wired_static_address_with_eth0_dhcp', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.NetCommon'] = wiredNetworkBranchFixture();
    $reader->values['NetWork.NetDHCP'] = networkBranchFixtures()['NetWork.NetDHCP'];

    $model = $editor->get('wired_network');
    assertSameValue(true, $model['values']['dhcp']);
    assertSameValue('192.168.1.121', $model['values']['hostIp']);
    assertSameValue('255.255.255.0', $model['values']['subnet']);
    assertSameValue('192.168.1.254', $model['values']['gateway']);
    assertSameValue(true, $model['fields']['mac']['disabled']);

    $reader->calls = [];
    $saved = $editor->update('wired_network', [
        'dhcp' => false,
        'hostIp' => '192.168.1.130',
        'subnet' => '255.255.255.0',
        'gateway' => '192.168.1.254',
        'hostName' => 'front-door',
        'mac' => 'malicious-change',
    ]);

    assertSameValue('0x8201A8C0', $reader->calls[2][2]['HostIP']);
    assertSameValue('00:12:33:08:53:78', $reader->calls[2][2]['MAC']);
    assertSameValue(false, $reader->calls[4][2][0]['Enable']);
    assertSameValue(false, $saved['values']['dhcp']);
    assertSameValue('192.168.1.130', $saved['values']['hostIp']);
});

test('network_editor_discovers_only_supported_forms_and_rejects_dummy_wifi', function (): void {
    $editor = networkEditorFixture($reader);
    $fixtures = networkBranchFixtures();
    $reader->values = $fixtures + rootFamilyFixtures($fixtures);
    unset($reader->values['Detect']['DetectTrack']);

    $available = $editor->available([
        'NetServerFunction' => ['WifiRouteSignalLevel' => true],
        'OtherFunction' => ['NOHDDRECORD' => true],
    ], [
        'AlarmInChannel' => 0,
        'AlarmOutChannel' => 0,
    ]);
    $ids = array_column($available, 'id');

    assertTrueValue(in_array('ftp', $ids, true));
    assertTrueValue(!in_array('wifi', $ids, true));
    assertTrueValue(!in_array('wifi_signal', $ids, true));
    assertTrueValue(!in_array('white_light', $ids, true));
    assertTrueValue(!in_array('detection_tracking', $ids, true));
    assertTrueValue(!in_array('snapshot', $ids, true));
    assertTrueValue(!in_array('recording', $ids, true));
    assertTrueValue(!in_array('alarm_output', $ids, true));
    assertTrueValue(!in_array('local_alarm', $ids, true));
});

test('snapshot_editor_remains_available_without_hard_disk_recording_when_camera_exposes_it', function (): void {
    $editor = networkEditorFixture($reader);
    $fixtures = networkBranchFixtures();
    $fixtures['Storage.Snapshot'] = [[
        'SnapMode' => 'ManualSnap', 'PreSnap' => 2, 'Redundancy' => false,
        'TimeSection' => ['preserve-schedule'],
    ]];
    $reader->values = $fixtures + rootFamilyFixtures($fixtures);

    $ids = array_column($editor->available(['OtherFunction' => ['NOHDDRECORD' => true]], []), 'id');
    assertTrueValue(in_array('snapshot', $ids, true));
    assertTrueValue(!in_array('recording', $ids, true));

    unset($reader->values['Storage']['Snapshot']);
    $ids = array_column($editor->available(['OtherFunction' => ['NOHDDRECORD' => true]], []), 'id');
    assertTrueValue(!in_array('snapshot', $ids, true));
});

test('snapshot_mode_has_documented_wire_values_and_rejects_unknown_submissions', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Storage.Snapshot'] = [[
        'SnapMode' => 'ManualSnap', 'PreSnap' => 2, 'Redundancy' => false,
        'TimeSection' => ['preserve-schedule'],
    ]];

    $model = $editor->get('snapshot');
    assertSameValue(['ClosedSnap', 'ManualSnap', 'ConfigSnap'], $model['fields']['mode']['options']);
    assertSameValue('Off', $model['fields']['mode']['optionLabels']['ClosedSnap']);
    assertSameValue('Manual snapshots', $model['fields']['mode']['optionLabels']['ManualSnap']);
    assertSameValue('Configured / scheduled snapshots', $model['fields']['mode']['optionLabels']['ConfigSnap']);
    assertSameValue('ManualSnap', $model['values']['mode']);

    $invalid = $model['values'];
    $invalid['mode'] = 'FakeSnap';
    assertThrows(static fn () => $editor->update('snapshot', $invalid), InvalidArgumentException::class);
    assertSameValue(false, in_array('set', array_column($reader->calls, 0), true));

    $valid = $model['values'];
    $valid['mode'] = 'ConfigSnap';
    $saved = $editor->update('snapshot', $valid);
    assertSameValue('ConfigSnap', $saved['values']['mode']);
    assertSameValue(['preserve-schedule'], $reader->values['Storage.Snapshot'][0]['TimeSection']);
});

test('snapshot_editor_reads_and_writes_parent_storage_when_direct_branch_is_rejected', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->errors['Storage.Snapshot'] = new DvripException('device_error', 'Door rejects direct snapshot reads.');
    $reader->values['Storage'] = [
        'Snapshot' => [[
            'SnapMode' => 'ManualSnap', 'PreSnap' => 2, 'Redundancy' => false,
            'TimeSection' => ['preserve-schedule'],
        ]],
        'StorageFailure' => ['Enable' => false],
    ];

    $model = $editor->get('snapshot');
    assertSameValue('ManualSnap', $model['values']['mode']);
    $editor->update('snapshot', $model['values']);
    assertSameValue(false, in_array('set', array_column($reader->calls, 0), true));
    $input = $model['values'];
    $input['mode'] = 'ConfigSnap';
    $saved = $editor->update('snapshot', $input);

    assertSameValue('ConfigSnap', $saved['values']['mode']);
    assertSameValue('ConfigSnap', $reader->values['Storage']['Snapshot'][0]['SnapMode']);
    assertSameValue(['preserve-schedule'], $reader->values['Storage']['Snapshot'][0]['TimeSection']);
    assertSameValue(['Enable' => false], $reader->values['Storage']['StorageFailure']);
    assertSameValue(false, in_array(['set', 'Storage.Snapshot'], array_map(
        static fn (array $call): array => array_slice($call, 0, 2),
        $reader->calls
    ), true));
});

test('network_editor_discovery_does_not_hide_transport_failures', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values = networkBranchFixtures();
    $reader->errors['NetWork'] = new DvripException('transport_timeout', 'timeout');

    $exception = assertThrows(
        static fn () => $editor->available([], []),
        DvripException::class
    );
    assertSameValue('transport_timeout', $exception->publicCode());
});

test('network_editor_discovery_reads_each_configuration_family_once', function (): void {
    $editor = networkEditorFixture($reader);
    $fixtures = networkBranchFixtures();
    $reader->values = $fixtures + rootFamilyFixtures($fixtures);

    $editor->available([
        'NetServerFunction' => ['NetWifi' => true],
        'OtherFunction' => ['SupportCameraWhiteLight' => true],
    ], []);

    $networkCalls = array_values(array_filter(
        $reader->calls,
        static fn (array $call): bool => $call[0] === 'get'
            && ($call[1] === 'NetWork' || str_starts_with($call[1], 'NetWork.'))
    ));
    assertSameValue([['get', 'NetWork']], $networkCalls);
});

test('network_editor_returns_current_ftp_password_in_editable_model', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.NetFTP'] = ftpBranchFixture();

    $model = $editor->get('ftp');
    assertSameValue('FTP', $model['label']);
    assertSameValue('NetWork.NetFTP', $model['branch']);
    assertSameValue(false, $model['values']['enable']);
    assertSameValue('ftp.example.test', $model['values']['server']);
    assertSameValue('keep-me', $model['values']['password']);
    assertTrueValue(!array_key_exists('configured', $model['fields']['password']));
});

test('network_editor_presents_wifi_codes_as_human_readable_selects', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.Wifi'] = [
        'Auth' => "\x01", 'Channel' => 0, 'Enable' => true, 'EncrypType' => "\x06",
        'GateWay' => '0xFE01A8C0', 'HostIP' => '0x7001A8C0', 'KeyType' => 1,
        'Keys' => 'keep-wifi', 'NetType' => 'Infra', 'SSID' => 'q001',
        'Submask' => '0x00FFFFFF',
    ];
    $reader->values['NetWork.NetDHCP'] = [
        ['Enable' => true, 'Interface' => 'eth0'],
        ['Enable' => false, 'Interface' => 'eth1'],
    ];

    $model = $editor->get('wifi');
    assertSameValue('WPA2PSK', $model['values']['auth']);
    assertSameValue('AES', $model['values']['encryption']);
    assertSameValue(['OPEN', 'SHARED', 'WEPAUTO', 'WPAPSK', 'WPA2PSK', 'WPANONE', 'WPA', 'WPA2'], $model['fields']['auth']['options']);
    assertSameValue(['NONE', 'WEP', 'TKIP', 'AES'], $model['fields']['encryption']['options']);
    assertSameValue('WPA2 Personal (password)', $model['fields']['auth']['optionLabels']['WPA2PSK']);
    assertSameValue('AES encryption', $model['fields']['encryption']['optionLabels']['AES']);
    assertSameValue('keep-wifi', $model['values']['key']);
    assertSameValue(['Infra', 'Adhoc'], $model['fields']['networkType']['options']);
    assertSameValue('Infrastructure', $model['fields']['networkType']['optionLabels']['Infra']);
    assertSameValue(array_map('strval', range(0, 13)), $model['fields']['channel']['options']);
    assertSameValue('Automatic', $model['fields']['channel']['optionLabels']['0']);
    assertSameValue('Password format', $model['fields']['keyType']['label']);
    assertSameValue(['0', '1'], $model['fields']['keyType']['options']);
    assertSameValue('Hexadecimal key', $model['fields']['keyType']['optionLabels']['0']);
    assertSameValue('Text password (ASCII)', $model['fields']['keyType']['optionLabels']['1']);
    assertSameValue(true, $model['fields']['keyType']['disabled']);
    assertSameValue(false, $model['values']['dhcp']);
    assertSameValue('dhcp', $model['fields']['hostIp']['disableWhen']);
    assertSameValue('192.168.1.112', $model['values']['hostIp']);
    assertSameValue('255.255.255.0', $model['values']['subnet']);
    assertSameValue('192.168.1.254', $model['values']['gateway']);

    $reader->calls = [];
    $reader->normalizeBalconWifiWrites = true;
    $editor->update('wifi', [
        'enable' => true, 'dhcp' => true, 'ssid' => 'q001', 'auth' => 'WPA2PSK',
        'encryption' => 'AES', 'key' => 'new-wifi', 'channel' => 0,
        'networkType' => 'Infra', 'keyType' => 1,
        'hostIp' => '192.168.1.112', 'subnet' => '255.255.255.0',
        'gateway' => '192.168.1.254',
    ]);
    assertSameValue('WPAPSK', $reader->calls[2][2]['Auth']);
    assertSameValue('TKIP', $reader->calls[2][2]['EncrypType']);
    assertSameValue('new-wifi', $reader->calls[2][2]['Keys']);
    assertSameValue('0x7001A8C0', $reader->calls[2][2]['HostIP']);
    assertSameValue(true, $reader->calls[4][2][1]['Enable']);
});

test('network_editor_gives_unknown_wifi_codes_human_readable_fallback_labels', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.Wifi'] = [
        'Auth' => "\x09", 'Channel' => 0, 'Enable' => true, 'EncrypType' => "\x07",
        'GateWay' => '0xFE01A8C0', 'HostIP' => '0x7001A8C0', 'KeyType' => 1,
        'Keys' => 'keep-wifi', 'NetType' => 'Infra', 'SSID' => 'q001',
        'Submask' => '0x00FFFFFF',
    ];
    $reader->values['NetWork.NetDHCP'] = [
        ['Enable' => true, 'Interface' => 'eth0'],
        ['Enable' => false, 'Interface' => 'eth1'],
    ];

    $model = $editor->get('wifi');
    assertSameValue('Unknown authentication value reported by camera', $model['fields']['auth']['optionLabels']['Code 9']);
    assertSameValue('Unknown encryption value reported by camera', $model['fields']['encryption']['optionLabels']['Code 7']);
});

test('network_editor_human_detection_uses_native_readable_rule_modes_and_preserves_geometry', function (): void {
    $editor = networkEditorFixture($reader);
    $branch = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.HumanDetection'] = $branch;

    $model = $editor->get('human_detection');
    assertSameValue('Intrusion area', $model['fields']['ruleType']['optionLabels']['1']);
    assertSameValue('Tripwire', $model['fields']['ruleType']['optionLabels']['0']);
    assertSameValue(1, $model['values']['ruleType']);
    assertSameValue(true, $model['values']['ruleEnabled']);
    assertSameValue(false, array_key_exists('algorithm', $model['values']));
    assertSameValue(false, array_key_exists('objectType', $model['values']));

    $input = [
        'enable' => true,
        'pushInterval' => 3000,
        'showRule' => 0,
        'showTrack' => 1,
        'ruleEnabled' => true,
        'ruleType' => 0,
    ];
    $saved = $editor->update('human_detection', $input);
    $written = $reader->calls[array_key_last($reader->calls) - 1][2];

    assertSameValue(0, $written[0]['PedRule'][0]['RuleType']);
    assertSameValue($branch[0]['PedRule'][0]['RuleRegion'], $written[0]['PedRule'][0]['RuleRegion']);
    assertSameValue($branch[0]['PedRule'][0]['RuleLine'], $written[0]['PedRule'][0]['RuleLine']);
    assertSameValue($branch[0]['PedRule'][1], $written[0]['PedRule'][1]);
    assertSameValue(0, $saved['values']['ruleType']);
});

test('human_rule_geometry_changes_only_selected_points_and_preserves_other_rules', function (): void {
    $editor = networkEditorFixture($reader);
    $branch = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.HumanDetection'] = $branch;
    $model = $editor->get('human_detection');
    assertSameValue(8192, $model['ruleGeometry']['extent']);
    assertSameValue($branch[0]['PedRule'][0]['RuleLine']['Pts'], $model['ruleGeometry']['line']);
    assertSameValue($branch[0]['PedRule'][0]['RuleRegion']['Pts'], $model['ruleGeometry']['region']);
    $input = $model['values'];
    $input['ruleGeometryChanges'] = ['line' => [
        'expected' => $model['ruleGeometry']['line'],
        'points' => ['StartX' => 200, 'StartY' => 300, 'StopX' => 7000, 'StopY' => 7100],
    ]];
    $editor->update('human_detection', $input);
    $written = $reader->values['Detect.HumanDetection'];
    assertSameValue($input['ruleGeometryChanges']['line']['points'], $written[0]['PedRule'][0]['RuleLine']['Pts']);
    assertSameValue($branch[0]['PedRule'][0]['RuleRegion'], $written[0]['PedRule'][0]['RuleRegion']);
    assertSameValue($branch[0]['PedRule'][1], $written[0]['PedRule'][1]);
    $region = [['X' => 200, 'Y' => 200], ['X' => 7000, 'Y' => 300], ['X' => 6800, 'Y' => 7000], ['X' => 300, 'Y' => 6800]];
    $input['ruleGeometryChanges'] = ['region' => [
        'expected' => $model['ruleGeometry']['region'], 'points' => $region,
    ]];
    $editor->update('human_detection', $input);
    $written = $reader->values['Detect.HumanDetection'];
    assertSameValue($region, $written[0]['PedRule'][0]['RuleRegion']['Pts']);
    assertSameValue($branch[0]['PedRule'][0]['RuleRegion']['AlarmDirect'], $written[0]['PedRule'][0]['RuleRegion']['AlarmDirect']);
});

test('human_rule_geometry_rejects_stale_or_invalid_points_before_write', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $model = $editor->get('human_detection');
    $input = $model['values'];
    $input['ruleGeometryChanges'] = ['line' => ['expected' => ['StartX' => 0], 'points' => $model['ruleGeometry']['line']]];
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
    assertSameValue([], array_values(array_filter($reader->calls, fn ($call) => $call[0] === 'set')));
    $input['ruleGeometryChanges'] = ['line' => ['expected' => $model['ruleGeometry']['line'], 'points' => ['StartX' => -1, 'StartY' => 0, 'StopX' => 1, 'StopY' => 1]]];
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
});

test('network_editor_human_detection_reads_and_writes_native_alert_actions', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $events = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false,
        'VoiceEnable' => false, 'BeepEnable' => false,
        'VoiceType' => 520,
    ], 'Unrelated' => ['preserve' => true]];
    $reader->values['Detect.MotionDetect.[0]'] = $events;
    $reader->systemFunction = ['OtherFunction' => ['SupportCloseVoiceTip' => true]];

    $model = $editor->get('human_detection');
    assertSameValue(false, $model['values']['appPush']);
    assertSameValue(false, $model['values']['sendEmail']);
    assertSameValue(false, $model['values']['alarmSound']);
    assertSameValue('boolean', $model['fields']['appPush']['type']);
    assertSameValue('boolean', $model['fields']['sendEmail']['type']);
    assertSameValue('boolean', $model['fields']['alarmSound']['type']);

    $input = $model['values'];
    $input['appPush'] = true;
    $input['sendEmail'] = true;
    $input['alarmSound'] = true;
    $saved = $editor->update('human_detection', $input);
    assertSameValue(true, $saved['values']['appPush']);
    assertSameValue(true, $saved['values']['sendEmail']);
    assertSameValue(true, $saved['values']['alarmSound']);
    assertSameValue(true, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['MessageEnable']);
    assertSameValue(true, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['MailEnable']);
    assertSameValue(true, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['VoiceEnable']);
    assertSameValue(false, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['BeepEnable']);
    assertSameValue(520, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['VoiceType']);
    assertSameValue($events['Unrelated'], $reader->values['Detect.MotionDetect.[0]']['Unrelated']);
    assertSameValue(true, in_array(['get', 'Detect.MotionDetect.[0]'], $reader->calls, true));
    assertSameValue(false, in_array(['get', 'NetWork.NetMail'], $reader->calls, true));
});

test('human_detection_ptz_link_is_shown_only_with_real_ptz_capability_and_preserves_other_channels', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $links = array_fill(0, 64, ['None', 0]);
    $links[1] = ['Tour', 3];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => false,
        'PtzEnable' => false, 'PtzLink' => $links,
    ]];
    $reader->systemFunction = ['OtherFunction' => [
        'SupportPTZDirectionControl' => true,
        'SupportSetPTZPresetAttribute' => true,
        'SupportPTZTour' => true,
    ]];
    $model = $editor->get('human_detection');
    assertSameValue('None', $model['values']['ptzAction']);
    assertSameValue(0, $model['values']['ptzNumber']);
    assertSameValue(['None', 'Preset', 'Tour', 'Pattern'], $model['fields']['ptzAction']['options']);
    $input = $model['values'];
    $input['ptzAction'] = 'Preset';
    $input['ptzNumber'] = 7;
    $saved = $editor->update('human_detection', $input);
    assertSameValue('Preset', $saved['values']['ptzAction']);
    assertSameValue(['Preset', 7], $reader->values['Detect.MotionDetect.[0]']['EventHandler']['PtzLink'][0]);
    assertSameValue(['Tour', 3], $reader->values['Detect.MotionDetect.[0]']['EventHandler']['PtzLink'][1]);
    assertSameValue(true, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['PtzEnable']);
});

test('human_detection_ptz_link_rejects_forged_or_invalid_controls', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => false,
        'PtzEnable' => true, 'PtzLink' => array_fill(0, 64, ['None', 0]),
    ]];
    $model = $editor->get('human_detection');
    assertSameValue(false, isset($model['fields']['ptzAction']));
    $input = $model['values'] + ['ptzAction' => 'Preset', 'ptzNumber' => 1];
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
    $reader->systemFunction = ['OtherFunction' => [
        'SupportPTZDirectionControl' => true,
        'SupportSetPTZPresetAttribute' => true,
    ]];
    $input = $editor->get('human_detection')['values'];
    $input['ptzAction'] = 'Pattern';
    $input['ptzNumber'] = 0;
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
    $input['ptzAction'] = 'Preset';
    $input['ptzNumber'] = 0;
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
    $reader->systemFunction = ['OtherFunction' => ['SupportPTZDirectionControl' => true]];
    assertSameValue(false, isset($editor->get('human_detection')['fields']['ptzAction']));
    assertSameValue([], array_values(array_filter($reader->calls, fn ($call) => $call[0] === 'set')));
});

test('human_detection_accepts_documented_pattern_link_and_preserves_legacy_zero_on_unrelated_save', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $links = array_fill(0, 64, ['None', 0]);
    $links[0] = ['Pattern', 0];
    $links[1] = ['Tour', 3];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => false,
        'PtzEnable' => true, 'PtzLink' => $links,
    ]];
    $reader->systemFunction = ['OtherFunction' => [
        'SupportPTZDirectionControl' => true,
        'SupportSetPTZPresetAttribute' => true,
        'SupportPTZTour' => true,
    ]];
    $model = $editor->get('human_detection');
    assertSameValue(['None', 'Preset', 'Tour', 'Pattern'], $model['fields']['ptzAction']['options']);
    assertSameValue('Pattern', $model['values']['ptzAction']);
    assertSameValue(0, $model['values']['ptzNumber']);
    assertSameValue(false, isset($model['fields']['ptzNumber']['max']));
    $editor->update('human_detection', $model['values']);
    assertSameValue(['Pattern', 0], $reader->values['Detect.MotionDetect.[0]']['EventHandler']['PtzLink'][0]);

    $input = $model['values'];
    $input['ptzNumber'] = 300;
    $saved = $editor->update('human_detection', $input);
    assertSameValue(['Pattern', 300], $reader->values['Detect.MotionDetect.[0]']['EventHandler']['PtzLink'][0]);
    assertSameValue(['Tour', 3], $reader->values['Detect.MotionDetect.[0]']['EventHandler']['PtzLink'][1]);
    assertSameValue(300, $saved['values']['ptzNumber']);
});

test('human_detection_none_link_does_not_require_a_number_and_writes_zero', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $links = array_fill(0, 64, ['None', 0]);
    $links[0] = ['Preset', 4];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => false,
        'PtzEnable' => true, 'PtzLink' => $links,
    ]];
    $reader->systemFunction = ['OtherFunction' => [
        'SupportPTZDirectionControl' => true,
        'SupportSetPTZPresetAttribute' => true,
    ]];
    $input = $editor->get('human_detection')['values'];
    $input['ptzAction'] = 'None';
    unset($input['ptzNumber']);
    $saved = $editor->update('human_detection', $input);
    assertSameValue(['None', 0], $reader->values['Detect.MotionDetect.[0]']['EventHandler']['PtzLink'][0]);
    assertSameValue(0, $saved['values']['ptzNumber']);
});

test('human_detection_discovers_ftp_and_log_actions_and_saves_only_their_shared_flags', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => true, 'MessageEnable' => false, 'VoiceEnable' => false,
        'FTPEnable' => false, 'LogEnable' => false, 'BeepEnable' => true,
    ], 'Unrelated' => ['preserve' => true]];
    $reader->systemFunction = ['NetServerFunction' => ['NetFTP' => true]];

    $model = $editor->get('human_detection');
    assertSameValue(false, $model['values']['ftpUpload']);
    assertSameValue(false, $model['values']['writeLog']);
    assertSameValue('boolean', $model['fields']['ftpUpload']['type']);
    assertSameValue('boolean', $model['fields']['writeLog']['type']);

    $input = $model['values'];
    $input['ftpUpload'] = true;
    $input['writeLog'] = true;
    $saved = $editor->update('human_detection', $input);
    assertSameValue(true, $saved['values']['ftpUpload']);
    assertSameValue(true, $saved['values']['writeLog']);
    $written = $reader->values['Detect.MotionDetect.[0]'];
    assertSameValue(true, $written['EventHandler']['FTPEnable']);
    assertSameValue(true, $written['EventHandler']['LogEnable']);
    assertSameValue(true, $written['EventHandler']['BeepEnable']);
    assertSameValue(['preserve' => true], $written['Unrelated']);
});

test('human_detection_hides_unavailable_ftp_and_log_actions_and_rejects_forged_flags', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => false,
        'FTPEnable' => false,
    ]];
    $reader->systemFunction = ['NetServerFunction' => ['NetFTP' => false]];

    $model = $editor->get('human_detection');
    assertSameValue(false, array_key_exists('ftpUpload', $model['fields']));
    assertSameValue(false, array_key_exists('writeLog', $model['fields']));
    $input = $model['values'];
    $input['ftpUpload'] = true;
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
    assertSameValue([], array_values(array_filter($reader->calls, fn ($call) => $call[0] === 'set')));

    unset($input['ftpUpload']);
    $input['writeLog'] = true;
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
    assertSameValue([], array_values(array_filter($reader->calls, fn ($call) => $call[0] === 'set')));
});

test('human_detection_snapshot_switch_sets_channel_one_mask_without_touching_other_alert_settings', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $events = ['EventHandler' => [
        'MailEnable' => true, 'MessageEnable' => false, 'VoiceEnable' => false,
        'SnapEnable' => false, 'SnapShotMask' => '0x00000000000000000000000000000000',
        'FTPEnable' => false, 'LogEnable' => true,
    ], 'Unrelated' => ['preserve' => true]];
    $reader->values['Detect.MotionDetect.[0]'] = $events;
    $model = $editor->get('human_detection');
    assertSameValue(false, $model['values']['snapshot']);
    assertSameValue('boolean', $model['fields']['snapshot']['type']);

    $input = $model['values'];
    $input['snapshot'] = true;
    $saved = $editor->update('human_detection', $input);
    $written = $reader->values['Detect.MotionDetect.[0]'];
    assertSameValue(true, $saved['values']['snapshot']);
    assertSameValue(true, $written['EventHandler']['SnapEnable']);
    assertSameValue('0x00000001000000000000000000000000', $written['EventHandler']['SnapShotMask']);
    assertSameValue(true, $written['EventHandler']['MailEnable']);
    assertSameValue(false, $written['EventHandler']['FTPEnable']);
    assertSameValue(true, $written['EventHandler']['LogEnable']);
    assertSameValue($events['Unrelated'], $written['Unrelated']);

    $input = $saved['values'];
    $input['snapshot'] = false;
    $saved = $editor->update('human_detection', $input);
    assertSameValue(false, $saved['values']['snapshot']);
    assertSameValue(false, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['SnapEnable']);
    assertSameValue('0x00000000000000000000000000000000', $reader->values['Detect.MotionDetect.[0]']['EventHandler']['SnapShotMask']);
});

test('human_detection_snapshot_preserves_other_channel_bits_and_hides_malformed_mask', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => false,
        'SnapEnable' => true, 'SnapShotMask' => '0x00000003000000000000000000000000',
    ]];
    $model = $editor->get('human_detection');
    assertSameValue(true, $model['values']['snapshot']);
    $input = $model['values'];
    $input['snapshot'] = false;
    $editor->update('human_detection', $input);
    assertSameValue(true, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['SnapEnable']);
    assertSameValue('0x00000002000000000000000000000000', $reader->values['Detect.MotionDetect.[0]']['EventHandler']['SnapShotMask']);

    $reader->values['Detect.MotionDetect.[0]']['EventHandler']['SnapShotMask'] = 'invalid';
    $model = $editor->get('human_detection');
    assertSameValue(false, array_key_exists('snapshot', $model['fields']));
});

test('network_editor_hides_alarm_sound_without_confirmed_voice_output', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = [
        'EventHandler' => ['MessageEnable' => false, 'MailEnable' => false, 'VoiceEnable' => false],
    ];
    $reader->systemFunction = ['OtherFunction' => ['SupportAlarmVoiceTips' => true]];

    $model = $editor->get('human_detection');
    assertSameValue(false, array_key_exists('alarmSound', $model['fields']));
    assertSameValue(false, $model['values']['appPush']);
    assertSameValue(false, $model['values']['sendEmail']);
});

test('human_detection_lists_only_camera_advertised_sound_types_and_writes_selected_code', function (): void {
    $editor = networkEditorFixture($reader, ['labels' => [524 => 'Bell Alarm']]);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => true,
        'VoiceType' => 524, 'BeepEnable' => false,
    ]];
    $reader->values['Ability.VoiceTipType'] = ['VoiceTip' => [
        ['VoiceEnum' => 24], ['VoiceEnum' => 523], ['VoiceEnum' => 524], ['VoiceEnum' => 550],
    ]];
    $reader->systemFunction = ['OtherFunction' => ['SupportCloseVoiceTip' => true]];

    $model = $editor->get('human_detection');
    assertSameValue(['24', '523', '524', '550'], $model['fields']['soundType']['options']);
    assertSameValue('Bell Alarm', $model['fields']['soundType']['optionLabels']['524']);
    assertSameValue(524, $model['values']['soundType']);

    $input = $model['values'];
    $input['soundType'] = 523;
    $saved = $editor->update('human_detection', $input);
    assertSameValue(523, $saved['values']['soundType']);
    assertSameValue(523, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['VoiceType']);
    assertSameValue(false, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['BeepEnable']);
});

test('human_detection_rejects_unadvertised_sound_code_before_any_write', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => true, 'VoiceType' => 524,
    ]];
    $reader->values['Ability.VoiceTipType'] = ['VoiceTip' => [['VoiceEnum' => 524]]];
    $reader->systemFunction = ['OtherFunction' => ['SupportCloseVoiceTip' => true]];
    $input = $editor->get('human_detection')['values'];
    $input['soundType'] = 550;
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);
    assertSameValue([], array_values(array_filter($reader->calls, fn ($call) => $call[0] === 'set')));
});

test('human_detection_hides_sound_selector_without_speaker_and_accepts_configured_fallback', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => false, 'VoiceType' => 520,
    ]];
    $reader->values['Ability.VoiceTipType'] = ['VoiceTip' => [['VoiceEnum' => 520]]];
    $model = $editor->get('human_detection');
    assertSameValue(false, array_key_exists('soundType', $model['fields']));
    $input = $model['values'];
    $input['soundType'] = 520;
    assertThrows(fn () => $editor->update('human_detection', $input), InvalidArgumentException::class);

    $editor = new NetworkServiceEditor($reader, [
        'supported' => true, 'options' => [520 => 'Configured voice'],
    ]);
    unset($reader->values['Ability.VoiceTipType']);
    $model = $editor->get('human_detection');
    assertSameValue(['520'], $model['fields']['soundType']['options']);
    assertSameValue('Configured voice', $model['fields']['soundType']['optionLabels']['520']);
});

test('human_detection_can_replace_unadvertised_current_sound_without_requiring_unrelated_changes', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => true, 'VoiceType' => 999,
    ]];
    $reader->values['Ability.VoiceTipType'] = ['VoiceTip' => [['VoiceEnum' => 524]]];
    $reader->systemFunction = ['OtherFunction' => ['SupportCloseVoiceTip' => true]];
    $model = $editor->get('human_detection');
    assertSameValue(999, $model['values']['soundType']);
    assertSameValue(['524', '999'], $model['fields']['soundType']['options']);
    assertSameValue('Current sound (not advertised by camera)', $model['fields']['soundType']['optionLabels']['999']);

    $input = $model['values'];
    $editor->update('human_detection', $input);
    assertSameValue(999, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['VoiceType']);
    $input['soundType'] = 524;
    $editor->update('human_detection', $input);
    assertSameValue(524, $reader->values['Detect.MotionDetect.[0]']['EventHandler']['VoiceType']);
});

test('human_detection_does_not_treat_fallback_option_names_as_verified_ability_labels', function (): void {
    $editor = networkEditorFixture($reader, ['options' => [524 => 'Unverified fallback name']]);
    $reader->values['Detect.HumanDetection'] = networkBranchFixtures()['Detect.HumanDetection'];
    $reader->values['Detect.MotionDetect.[0]'] = ['EventHandler' => [
        'MailEnable' => false, 'MessageEnable' => false, 'VoiceEnable' => true, 'VoiceType' => 524,
    ]];
    $reader->values['Ability.VoiceTipType'] = ['VoiceTip' => [['VoiceEnum' => 524]]];
    $reader->systemFunction = ['OtherFunction' => ['SupportCloseVoiceTip' => true]];
    $model = $editor->get('human_detection');
    assertSameValue('Camera sound option 524 (label not verified)', $model['fields']['soundType']['optionLabels']['524']);
});

test('network_editor_maps_both_balcon_aes_control_bytes_to_aes', function (): void {
    foreach ([5, 6] as $code) {
        $editor = networkEditorFixture($reader);
        $reader->values['NetWork.Wifi'] = [
            'Auth' => "\x01", 'Channel' => 0, 'Enable' => true, 'EncrypType' => chr($code),
            'GateWay' => '0xFE01A8C0', 'HostIP' => '0x7001A8C0', 'KeyType' => 1,
            'Keys' => 'keep-wifi', 'NetType' => 'Infra', 'SSID' => 'q001',
            'Submask' => '0x00FFFFFF',
        ];
        $reader->values['NetWork.NetDHCP'] = [
            ['Enable' => true, 'Interface' => 'eth0'],
            ['Enable' => false, 'Interface' => 'eth1'],
        ];
        assertSameValue('AES', $editor->get('wifi')['values']['encryption']);
    }
});

test('network_editor_maps_balcon_settled_wifi_aliases_to_actual_radio_modes', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.Wifi'] = [
        'Auth' => 'WPAPSK', 'Channel' => 0, 'Enable' => true, 'EncrypType' => 'TKIP',
        'GateWay' => '0xFE01A8C0', 'HostIP' => '0x7001A8C0', 'KeyType' => 1,
        'Keys' => 'keep-wifi', 'NetType' => 'Infra', 'SSID' => 'q001',
        'Submask' => '0x00FFFFFF',
    ];
    $reader->values['NetWork.NetDHCP'] = [
        ['Enable' => true, 'Interface' => 'eth0'],
        ['Enable' => false, 'Interface' => 'eth1'],
    ];

    $model = $editor->get('wifi');
    assertSameValue('WPA2PSK', $model['values']['auth']);
    assertSameValue('AES', $model['values']['encryption']);
});

test('network_editor_merges_ftp_fields_and_clears_password_when_submitted_empty', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.NetFTP'] = ftpBranchFixture();

    $result = $editor->update('ftp', [
        'enable' => true,
        'server' => 'new.example.test',
        'port' => 2121,
        'username' => 'uploader',
        'password' => '',
        'directory' => '/balcon',
        'maxFileLen' => 256,
    ]);

    $written = $reader->calls[1][2];
    assertSameValue('', $written['Server']['Password']);
    assertSameValue('0x00000000', $written['Server']['Address']);
    assertSameValue(false, $written['Server']['Anonymity']);
    assertSameValue('/balcon', $written['Directory']);
    assertSameValue('new.example.test', $result['values']['server']);
    assertSameValue([
        ['get', 'NetWork.NetFTP'],
        ['set', 'NetWork.NetFTP', $written],
        ['get', 'NetWork.NetFTP'],
    ], $reader->calls);
});

test('network_editor_maps_first_thirty_forms_to_their_exact_branches', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values = networkBranchFixtures();

    foreach ([
        'ftp' => 'NetWork.NetFTP',
        'ntp' => 'NetWork.NetNTP',
        'upnp' => 'NetWork.Upnp',
        'pms' => 'NetWork.PMS',
        'das' => 'NetWork.DAS',
        'multicast' => 'NetWork.Multicast',
        'pppoe' => 'NetWork.NetPPPoE',
        'rtsp' => 'NetWork.RTSP',
        'serial_bridge' => 'NetWork.TranCommData',
        'nat' => 'NetWork.Nat',
        'wired_network' => 'NetWork.NetCommon',
        'dhcp' => 'NetWork.NetDHCP',
        'wifi' => 'NetWork.Wifi',
        'wifi_signal' => 'NetWork.WifiStrengthLevel',
        'vendor_connections' => 'NetWork.DisableXmServerConn',
        'online_upgrade' => 'NetWork.OnlineUpgrade',
        'ddns_1' => 'NetWork.NetDDNS',
        'ddns_2' => 'NetWork.NetDDNS',
        'ddns_3' => 'NetWork.NetDDNS',
        'ddns_4' => 'NetWork.NetDDNS',
        'ddns_5' => 'NetWork.NetDDNS',
        'general_preferences' => 'General.General',
        'locale_formats' => 'General.Location',
        'defog' => 'Camera.ClearFog',
        'white_light' => 'Camera.WhiteLight',
        'overlay_indicators' => 'fVideo.GUISet',
        'osd_logo' => 'fVideo.OsdLogo',
        'output_volume' => 'fVideo.Volume',
        'input_volume' => 'fVideo.VolumeIn',
        'detection_tracking' => 'Detect.DetectTrack',
        'human_detection' => 'Detect.HumanDetection',
    ] as $id => $branch) {
        assertSameValue($branch, $editor->get($id)['branch']);
    }
});

test('network_editor_marks_connectivity_sensitive_forms_with_warnings', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values = networkBranchFixtures();
    assertTrueValue(str_contains($editor->get('rtsp')['warning'], 'MotionEye'));
    assertTrueValue(str_contains($editor->get('pppoe')['warning'], 'network'));
    assertTrueValue(str_contains($editor->get('wifi')['warning'], 'AP search'));
});

test('network_editor_rejects_invalid_values_before_camera_read', function (): void {
    $editor = networkEditorFixture($reader);
    assertThrows(static fn () => $editor->update('upnp', [
        'enable' => true,
        'httpPort' => 0,
        'mediaPort' => 34567,
        'mobilePort' => 34599,
    ]), InvalidArgumentException::class);
    assertSameValue([], $reader->calls);
});

test('network_editor_rejects_incomplete_camera_branch_before_write', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.PMS'] = ['Enable' => false];
    assertThrows(static fn () => $editor->update('pms', [
        'enable' => false, 'boxId' => '', 'server' => '', 'port' => 80, 'pushInterval' => 10,
    ]), DvripException::class);
    assertSameValue([['get', 'NetWork.PMS']], $reader->calls);
});

test('network_editor_rejects_readback_mismatch', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values = networkBranchFixtures();
    $reader->persistWrites = false;
    $exception = assertThrows(static fn () => $editor->update('ntp', [
        'enable' => true,
        'server' => 'pool.ntp.org',
        'port' => 123,
        'timeZone' => 14,
        'updatePeriod' => 30,
    ]), DvripException::class);
    assertSameValue('write_verification_failed', $exception->publicCode());
});

function ftpBranchFixture(): array
{
    return [
        'Directory' => '', 'Enable' => false, 'MaxFileLen' => 128,
        'Server' => [
            'Address' => '0x00000000', 'Anonymity' => false,
            'Name' => 'ftp.example.test', 'Password' => 'keep-me',
            'Port' => 21, 'UserName' => 'camera',
        ],
    ];
}

function wiredNetworkBranchFixture(): array
{
    return [
        'GateWay' => '0xFE01A8C0',
        'HostIP' => '0x7901A8C0',
        'HostName' => 'LocalHost',
        'HttpPort' => 80,
        'MAC' => '00:12:33:08:53:78',
        'MaxBps' => 0,
        'MonMode' => 'TCP',
        'SSLPort' => 8443,
        'Submask' => '0x00FFFFFF',
        'TCPMaxConn' => 10,
        'TCPPort' => 34567,
        'TransferPlan' => 'Transmission',
        'UDPPort' => 34568,
        'UseHSDownLoad' => true,
    ];
}

function networkBranchFixtures(): array
{
    return [
        'NetWork.NetFTP' => ftpBranchFixture(),
        'NetWork.NetCommon' => wiredNetworkBranchFixture(),
        'NetWork.NetNTP' => [
            'Enable' => true,
            'Server' => ['Address' => '0x00000000', 'Anonymity' => false, 'Name' => 'time.example.test', 'Password' => '', 'Port' => 123, 'UserName' => ''],
            'TimeZone' => 14, 'UpdatePeriod' => 10,
        ],
        'NetWork.Upnp' => ['Enable' => false, 'HTTPPort' => 80, 'MediaPort' => 34567, 'MobilePort' => 34599, 'State' => false],
        'NetWork.PMS' => ['BoxID' => '', 'Enable' => false, 'Port' => 80, 'PushInterval' => 10, 'ServName' => 'push.example.test'],
        'NetWork.DAS' => ['DeviceID' => '123456', 'Enable' => false, 'Password' => 'keep-das', 'Port' => 9400, 'ServerAddr' => '192.0.2.50', 'UserName' => 'admin'],
        'NetWork.Multicast' => ['Addr' => '239.0.1.0', 'Enable' => true, 'Port' => 32002],
        'NetWork.NetPPPoE' => ['Enable' => false, 'HostIP' => '0x00000000', 'Server' => ['Address' => '0x00000000', 'Anonymity' => false, 'Name' => 'PPPoE', 'Password' => 'keep-pppoe', 'Port' => 1900, 'UserName' => '']],
        'NetWork.RTSP' => ['Client' => ['Address' => '0x00000000', 'Anonymity' => false, 'Name' => '', 'Password' => 'keep-client', 'Port' => 554, 'UserName' => ''], 'IsClient' => false, 'IsServer' => true, 'Server' => ['Address' => '0x00000000', 'Anonymity' => false, 'Name' => '', 'Password' => 'keep-rtsp', 'Port' => 554, 'UserName' => '']],
        'NetWork.TranCommData' => ['SerialType' => 1, 'ServerAddr' => ' ', 'ServerPort' => 9530, 'ServerType' => 0, 'bEnable' => false],
        'NetWork.Nat' => ['Addr' => 'secu100.net', 'DnsServer1' => '0xBC007C70', 'DnsServer2' => '0xEC845436', 'NatEnable' => false, 'Port' => 8765, 'XMeyeMTU' => 1280],
        'NetWork.NetDHCP' => [
            ['Enable' => true, 'Interface' => 'eth0'], ['Enable' => false, 'Interface' => 'eth1'],
            ['Enable' => false, 'Interface' => 'eth2'], ['Enable' => false, 'Interface' => 'eth3'],
            ['Enable' => false, 'Interface' => ''],
        ],
        'NetWork.Wifi' => ['Auth' => 'WPAPSK', 'Channel' => 0, 'Enable' => false, 'EncrypType' => 'TKIP', 'GateWay' => '0xFE01A8C0', 'HostIP' => '0x7001A8C0', 'KeyType' => 1, 'Keys' => 'keep-wifi', 'NetType' => 'Infra', 'SSID' => 'fixture', 'Submask' => '0x00FFFFFF'],
        'NetWork.WifiStrengthLevel' => ['Excellent' => 0, 'Good' => -67, 'Low' => -71, 'NoSingnal' => -90, 'VeryGood' => -57, 'VeryLow' => -81],
        'NetWork.DisableXmServerConn' => ['DisableAll' => false, 'DisableDSS' => false, 'DisableNAT' => false, 'DisableRPS' => false],
        'NetWork.OnlineUpgrade' => ['AutoCheck' => 0, 'AutoUpgradeImp' => 0, 'Enable' => 1, 'IgnoreAllVersion' => 0, 'IgnoreCurTime' => 1, 'IgnoreCurVersion' => 0, 'JustImpVersion' => 0, 'ServerAddr' => 'upgrade.example.test', 'ServerPort' => 8083],
        'NetWork.NetDDNS' => array_map(static fn (int $index): array => [
            'DDNSKey' => "keep-key-{$index}", 'Enable' => false, 'HostName' => "host-{$index}.example.test",
            'Server' => ['Address' => '0x0100060A', 'Anonymity' => false, 'Name' => "provider-{$index}.example.test", 'Password' => "keep-password-{$index}", 'Port' => 80, 'UserName' => ''],
        ], range(1, 5)),
        'General.General' => ['AutoLogout' => 0, 'FontSize' => 24, 'IranCalendarEnable' => 0, 'LocalNo' => 0, 'MachineName' => 'LocalHost', 'OverWrite' => 'OverWrite', 'ScreenAutoShutdown' => 0, 'ScreenSaveTime' => 0, 'VideoOutPut' => 'Auto'],
        'General.Location' => ['DSTEnd' => ['Day' => 1], 'DSTRule' => 'Off', 'DSTStart' => ['Day' => 1], 'DateFormat' => 'YYMMDD', 'DateSeparator' => '-', 'IranCalendar' => 0, 'Language' => 'English', 'TimeFormat' => '24', 'VideoFormat' => 'PAL', 'Week' => null, 'WorkDay' => 62],
        'Camera.ClearFog' => [['enable' => false, 'level' => 50]],
        'Camera.WhiteLight' => ['Brightness' => 50, 'MoveTrigLight' => ['Duration' => 60, 'Level' => 3], 'WorkMode' => 'Intelligent', 'WorkPeriod' => ['EHour' => 6, 'EMinute' => 0, 'Enable' => 1, 'SHour' => 18, 'SMinute' => 0]],
        'fVideo.GUISet' => ['AlarmStateEnable' => true, 'CarInfo' => false, 'ChanStateBitRateEnable' => true, 'ChanStateLckEnable' => true, 'ChanStateMtdEnable' => true, 'ChanStateRecEnable' => false, 'ChanStateVlsEnable' => true, 'ChannelTitleEnable' => true, 'Deflick' => true, 'GPSInfo' => true, 'RecordStateEnable' => true, 'RemoteEnable' => true, 'TimeTitleEnable' => true, 'WindowAlpha' => 128],
        'fVideo.OsdLogo' => ['BgTrans' => 16, 'Enable' => true, 'FgTrans' => 96, 'Left' => 80, 'Top' => 80],
        'fVideo.Volume' => [['AudioMode' => 'Single', 'LeftVolume' => 100, 'RightVolume' => 100]],
        'fVideo.VolumeIn' => [['AudioMode' => 'Single', 'LeftVolume' => 50, 'RightVolume' => 50]],
        'Detect.DetectTrack' => ['Sensitivity' => 1],
        'Detect.HumanDetection' => [[
            'Enable' => true, 'ObjectType' => 0, 'PedFdrAlg' => 0,
            'PedRule' => [
                ['Enable' => true, 'RuleLine' => ['AlarmDirect' => 2, 'Pts' => ['StartX' => 100, 'StartY' => 100, 'StopX' => 8100, 'StopY' => 8100]], 'RuleRegion' => ['AlarmDirect' => 2, 'Pts' => [['X' => 100, 'Y' => 100], ['X' => 8100, 'Y' => 100], ['X' => 8100, 'Y' => 8100], ['X' => 100, 'Y' => 8100]], 'PtsNum' => 4], 'RuleType' => 1],
                ['Enable' => false, 'RuleLine' => ['AlarmDirect' => 2, 'Pts' => ['StartX' => 100, 'StartY' => 100, 'StopX' => 8100, 'StopY' => 8100]], 'RuleRegion' => ['AlarmDirect' => 2, 'Pts' => [['X' => 100, 'Y' => 100], ['X' => 8100, 'Y' => 100], ['X' => 8100, 'Y' => 8100], ['X' => 100, 'Y' => 8100]], 'PtsNum' => 4], 'RuleType' => 1],
                ['Enable' => false, 'RuleLine' => ['AlarmDirect' => 2, 'Pts' => ['StartX' => 100, 'StartY' => 100, 'StopX' => 8100, 'StopY' => 8100]], 'RuleRegion' => ['AlarmDirect' => 2, 'Pts' => [['X' => 100, 'Y' => 100], ['X' => 8100, 'Y' => 100], ['X' => 8100, 'Y' => 8100], ['X' => 100, 'Y' => 8100]], 'PtsNum' => 4], 'RuleType' => 1],
                ['Enable' => false, 'RuleLine' => ['AlarmDirect' => 2, 'Pts' => ['StartX' => 100, 'StartY' => 100, 'StopX' => 8100, 'StopY' => 8100]], 'RuleRegion' => ['AlarmDirect' => 2, 'Pts' => [['X' => 100, 'Y' => 100], ['X' => 8100, 'Y' => 100], ['X' => 8100, 'Y' => 8100], ['X' => 100, 'Y' => 8100]], 'PtsNum' => 4], 'RuleType' => 1],
            ],
            'PushInterval' => 3000, 'Sensitivity' => 1, 'ShowRule' => 0, 'ShowTrack' => 1,
        ]],
    ];
}

function snapshotImageBranchFixture(): array
{
    $profile = static fn (bool $enabled, string $private): array => [
        'Audio' => ['BitRate' => 64, 'Compression' => 'G711A'],
        'AudioEnable' => false,
        'VideoEnable' => $enabled,
        'Video' => [
            'BitRate' => 512,
            'BitRateControl' => 'VBR',
            'Compression' => 'H.264',
            'FPS' => 1,
            'GOP' => 2,
            'Quality' => 4,
            'Resolution' => 'D1',
            'VirtualGOP' => 1,
        ],
        'VendorPrivate' => $private,
    ];
    return [[
        'MainFormat' => ['preserve-main-stream'],
        'SnapFormat' => [
            $profile(true, 'keep-profile'),
            $profile(false, 'keep-motion-profile'),
            $profile(false, 'keep-alarm-profile'),
        ],
        'VendorPrivate' => 'keep-encode',
    ]];
}

function rootFamilyFixtures(array $branches): array
{
    $roots = [];
    foreach ($branches as $branch => $value) {
        if ($branch === 'Simplify.Encode' || !str_contains($branch, '.')) {
            $roots[$branch] = $value;
            continue;
        }
        [$root, $child] = explode('.', $branch, 2);
        $roots[$root][$child] = $value;
    }
    return $roots;
}
