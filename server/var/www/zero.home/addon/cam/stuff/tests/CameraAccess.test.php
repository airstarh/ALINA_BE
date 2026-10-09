<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/php/CameraAccess.php';

use Cam\CameraAccess;
use Cam\AccessProbe;

test('access_inventory_uses_each_camera_ports_and_does_not_claim_configured_tls_is_active', function (): void {
    $camera = ['host' => '192.0.2.1', 'dvrip' => ['port' => 34570, 'username' => 'alice', 'password' => 'secret']];
    $network = ['NetCommon' => ['HttpPort' => 81, 'SSLPort' => 8443, 'UDPPort' => 34568],
        'RTSP' => ['IsServer' => true, 'Server' => ['Port' => 8554]]];
    $observed = ['tcp' => [81 => true, 8443 => false, 8554 => true, 8000 => true],
        'http' => [81 => ['url' => 'http://192.0.2.1:81/', 'status' => 200]], 'rtsp' => true];
    $model = CameraAccess::model($camera, $network, $observed);
    $points = array_column($model['points'], null, 'id');
    assertSameValue(34570, $points['dvrip']['port']);
    assertSameValue(81, $points['http']['port']);
    assertSameValue(8554, $points['rtsp']['port']);
    assertTrueValue(!isset($points['https']));
    assertSameValue('secret', $points['dvrip']['password']);
    assertSameValue(null, $points['tcp_8000']['password']);
    assertSameValue('TCP open; protocol unknown', $points['tcp_8000']['state']);
    assertTrueValue(!isset($points['dvrip_udp']));
    assertSameValue('dvrip://192.0.2.1:34570/', $points['dvrip']['url']);
    assertSameValue([8443], $model['unreachablePorts']);
});

test('access_discovery_uses_camera_oem_onvif_port_instead_of_stale_override', function (): void {
    $camera = ['dvrip' => ['port' => 34567], 'access' => ['ports' => ['onvif' => 8899]]];
    assertSameValue(9988, CameraAccess::ports($camera, [], ['Port' => 9988])['onvif']);
});
test('access_inventory_keeps_service_credentials_and_empty_passwords_distinct_from_unknown', function (): void {
    $camera = ['host' => '192.0.2.2', 'dvrip' => ['port' => 34567, 'username' => 'alice', 'password' => ''],
        'access' => ['ports' => ['onvif' => 9000], 'credentials' => ['onvif' => ['username' => 'bob', 'password' => 'other']]]];
    $observed = ['tcp' => [554 => true, 80 => true], 'onvif' => ['url' => 'http://192.0.2.2:9000/onvif/device_service', 'services' => []], 'discovery' => true];
    $model = CameraAccess::model($camera, ['OnvifPwdCheckout' => ['Enable' => false]], $observed);
    $points = array_column($model['points'], null, 'id');
    assertSameValue(9000, CameraAccess::ports($camera, [])['onvif']);
    assertSameValue('', $points['dvrip']['password']);
    assertSameValue(null, $points['rtsp']['password']);
    assertSameValue(null, $points['http']['password']);
    assertSameValue('bob', $points['onvif']['username']);
    assertSameValue('other', $points['onvif']['password']);
    assertTrueValue(str_contains($points['onvif']['note'], 'Password checking is disabled'));
    assertTrueValue(!isset($points['discovery']));
    assertTrueValue(!isset($points['ftp']));
});

test('access_inventory_uses_rtsp_specific_credentials_without_reusing_dvrip_password', function (): void {
    $camera = ['host' => '192.0.2.2', 'dvrip' => ['port' => 34567, 'username' => 'alice', 'password' => '']];
    $network = ['RTSP' => ['Server' => ['UserName' => 'viewer', 'Password' => 'stream-secret']]];
    $points = array_column(CameraAccess::model($camera, $network, ['tcp' => [554 => true]])['points'], null, 'id');
    assertSameValue('viewer', $points['rtsp']['username']);
    assertSameValue('stream-secret', $points['rtsp']['password']);
    $camera['access']['credentials']['rtsp'] = ['username' => 'configured', 'password' => 'confirmed-secret'];
    $points = array_column(CameraAccess::model($camera, $network, ['tcp' => [554 => true]])['points'], null, 'id');
    assertSameValue('confirmed-secret', $points['rtsp']['password']);
    $network['RTSP']['Server'] = ['UserName' => '', 'Password' => ''];
    unset($camera['access']);
    $points = array_column(CameraAccess::model($camera, $network, ['tcp' => [554 => true]])['points'], null, 'id');
    assertSameValue(null, $points['rtsp']['password']);
});

test('onvif_service_parser_ignores_faults_and_decodes_service_urls', function (): void {
    assertSameValue([], AccessProbe::services('<s:Fault><Reason>No access</Reason></s:Fault>'));
    assertSameValue([['namespace' => 'http://www.onvif.org/ver10/media/wsdl', 'url' => 'http://192.0.2.1:8899/media?a=1&b=2']],
        AccessProbe::services('<d:GetServicesResponse><d:Service><d:Namespace>http://www.onvif.org/ver10/media/wsdl</d:Namespace>'
            . '<d:XAddr>http://192.0.2.1:8899/media?a=1&amp;b=2</d:XAddr></d:Service></d:GetServicesResponse>'));
});

test('rtsp_templates_remove_credentials_without_losing_channel_and_stream', function (): void {
    assertSameValue('rtsp://192.0.2.1:554/stream0', AccessProbe::streamUrl('rtsp://alice:secret@192.0.2.1:554/stream0'));
    assertSameValue('rtsp://192.0.2.1:554/user={login}&password={password}&channel=1&stream=0.sdp',
        AccessProbe::streamUrl('rtsp://192.0.2.1:554/user=alice&password=secret&channel=1&stream=0.sdp'));
    assertSameValue('rtsp://192.0.2.1:554/user={login}_password={password}_channel=1_stream=1.sdp?real_stream',
        AccessProbe::streamUrl('rtsp://192.0.2.1:554/user=alice_password=sec_ret_channel=1_stream=1.sdp?real_stream'));
});
