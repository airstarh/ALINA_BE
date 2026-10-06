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

function networkEditorFixture(?object &$reader = null): NetworkServiceEditor
{
    loadNetworkServiceEditorClass();
    $reader = new class implements DvripReader {
        public array $values = [];
        public array $errors = [];
        public array $calls = [];
        public bool $persistWrites = true;
        public bool $normalizeBalconWifiWrites = false;
        public array $systemFunction = [];

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
        public function testMail(array $settings): bool { return true; }
        public function reboot(): bool { return true; }
        public function close(): void {}
    };
    return new NetworkServiceEditor($reader);
}

test('network_editor_lists_exact_sixty_eight_allowlisted_forms', function (): void {
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
            'main_stream', 'extra_stream', 'smart_h264', 'video_color_day', 'video_color_night', 'video_widget',
            'snapshot', 'recording', 'storage_failure', 'storage_low_space', 'storage_missing',
            'alarm_output', 'local_alarm', 'network_abort_alarm', 'network_alarm', 'ip_conflict_alarm',
            'uart_comm_1', 'ptz_serial', 'rs485',
        ],
        array_column($editor->list(), 'id')
    );
    assertThrows(static fn () => $editor->get('netcommon'), InvalidArgumentException::class);
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
