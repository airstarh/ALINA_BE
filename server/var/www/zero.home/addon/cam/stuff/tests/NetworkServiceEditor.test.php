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

test('network_editor_lists_exact_ten_allowlisted_forms', function (): void {
    $editor = networkEditorFixture();
    assertSameValue(
        ['ftp', 'ntp', 'upnp', 'pms', 'das', 'multicast', 'pppoe', 'rtsp', 'serial_bridge', 'nat'],
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

test('network_editor_maps_all_ten_forms_to_their_exact_branches', function (): void {
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
    ];
}
