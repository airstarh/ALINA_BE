<?php

declare(strict_types=1);

use Cam\DvripException;
use Cam\DvripReader;
use Cam\NetworkServiceEditor;

require_once dirname(__DIR__) . '/php/DvripException.php';
require_once dirname(__DIR__) . '/php/DvripReader.php';

function loadNetworkServiceEditorClass(): void
{
    require_once dirname(__DIR__) . '/php/NetworkServiceEditor.php';
}

function networkEditorFixture(?object &$reader = null): NetworkServiceEditor
{
    loadNetworkServiceEditorClass();
    $reader = new class implements DvripReader {
        public array $values = [];
        public array $calls = [];
        public bool $persistWrites = true;

        public function getConfig(string $command): mixed
        {
            $this->calls[] = ['get', $command];
            return $this->values[$command] ?? null;
        }

        public function setConfig(string $command, mixed $value): bool
        {
            $this->calls[] = ['set', $command, $value];
            if ($this->persistWrites) $this->values[$command] = $value;
            return true;
        }

        public function getSystemInfo(): array { return []; }
        public function getSystemFunction(): array { return []; }
        public function close(): void {}
    };
    return new NetworkServiceEditor($reader);
}

test('network_editor_lists_exact_thirty_allowlisted_forms', function (): void {
    $editor = networkEditorFixture();
    assertSameValue(
        [
            'ftp', 'ntp', 'upnp', 'pms', 'das', 'multicast', 'pppoe', 'rtsp', 'serial_bridge', 'nat',
            'dhcp', 'wifi', 'wifi_signal', 'vendor_connections', 'online_upgrade',
            'ddns_1', 'ddns_2', 'ddns_3', 'ddns_4', 'ddns_5',
            'general_preferences', 'locale_formats', 'defog', 'white_light', 'overlay_indicators',
            'osd_logo', 'output_volume', 'input_volume', 'detection_tracking', 'human_detection',
        ],
        array_column($editor->list(), 'id')
    );
    assertThrows(static fn () => $editor->get('netcommon'), InvalidArgumentException::class);
});

test('network_editor_returns_typed_ftp_model_without_password', function (): void {
    $editor = networkEditorFixture($reader);
    $reader->values['NetWork.NetFTP'] = ftpBranchFixture();

    $model = $editor->get('ftp');
    assertSameValue('FTP', $model['label']);
    assertSameValue('NetWork.NetFTP', $model['branch']);
    assertSameValue(false, $model['values']['enable']);
    assertSameValue('ftp.example.test', $model['values']['server']);
    assertSameValue(true, $model['fields']['password']['configured']);
    assertSameValue('', $model['values']['password']);
    assertTrueValue(!str_contains(json_encode($model), 'keep-me'));
});

test('network_editor_merges_ftp_fields_and_preserves_password_and_metadata', function (): void {
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
    assertSameValue('keep-me', $written['Server']['Password']);
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

test('network_editor_maps_all_thirty_forms_to_their_exact_branches', function (): void {
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

function networkBranchFixtures(): array
{
    return [
        'NetWork.NetFTP' => ftpBranchFixture(),
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
        'Detect.HumanDetection' => [['Enable' => true, 'ObjectType' => 0, 'PedFdrAlg' => 0, 'PedRule' => [['Enable' => true]], 'PushInterval' => 3000, 'Sensitivity' => 1, 'ShowRule' => 0, 'ShowTrack' => 1]],
    ];
}
