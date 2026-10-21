<?php

declare(strict_types=1);

namespace Cam;

require_once __DIR__ . '/AccessProbe.php';

final class CameraAccess
{
    public static function ports(array $camera, array $network, array $correspondent = []): array
    {
        $common = $network['NetCommon'] ?? [];
        $defaults = ['dvrip' => $camera['dvrip']['port'], 'http' => $common['HttpPort'] ?? 80,
            'https' => $common['SSLPort'] ?? 443, 'rtsp' => $network['RTSP']['Server']['Port'] ?? 554, 'onvif' => 8899];
        foreach ($camera['access']['ports'] ?? [] as $key => $port) $defaults[$key] = $port;
        // The authenticated connection is the authority for DVRIP, not a stale network template.
        $defaults['dvrip'] = $camera['dvrip']['port'];
        if (is_int($correspondent['Port'] ?? null)) $defaults['onvif'] = $correspondent['Port'];
        return array_filter($defaults, static fn ($v) => is_int($v) && $v > 0 && $v <= 65535);
    }

    public static function correspondent(DvripReader $reader): array
    {
        try { $value = $reader->getConfig('OEMcfg.Correspondent'); }
        catch (DvripException $error) {
            if (!in_array($error->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) throw $error;
            return [];
        }
        return is_array($value) ? $value : [];
    }

    public static function model(array $camera, array $network, array $observed): array
    {
        $host = $camera['host'];
        $address = str_contains($host, ':') ? '[' . trim($host, '[]') . ']' : $host;
        $ports = self::ports($camera, $network);
        $points = [];
        $used = [];
        $add = static function (string $id, string $name, string $transport, ?int $port, string $state,
            string $note, array $endpoints = [], bool $account = false) use (&$points, &$used, $camera, $host, $network): void {
            if ($transport === 'TCP' && $port !== null) $used[$port] = true;
            $credential = $camera['access']['credentials'][$id] ?? ($id === 'dvrip' ? $camera['dvrip'] : null);
            $explicit = isset($camera['access']['credentials'][$id]);
            $cameraReported = false;
            $rtspAccount = $network['RTSP']['Server'] ?? [];
            if (!$explicit && $id === 'rtsp' && is_string($rtspAccount['UserName'] ?? null)
                && $rtspAccount['UserName'] !== '' && is_string($rtspAccount['Password'] ?? null)
                && $rtspAccount['Password'] !== '') {
                $credential = ['username' => $rtspAccount['UserName'], 'password' => $rtspAccount['Password']];
                $cameraReported = true;
            }
            $points[] = ['id' => $id, 'name' => $name, 'transport' => $transport, 'host' => $host,
                'port' => $port, 'state' => $state, 'note' => $note, 'endpoints' => $endpoints,
                'username' => $credential['username'] ?? null, 'password' => $credential['password'] ?? null,
                'credentialNote' => $explicit ? 'Per-service credentials from config.php; login not tested.'
                    : ($id === 'dvrip' ? 'Camera account used successfully by this application.'
                        : ($cameraReported ? 'RTSP credentials reported by camera settings; login not tested.'
                            : 'No verified login details for this endpoint.'))];
        };
        $add('dvrip', 'DVRIP / NETIP', 'TCP', $ports['dvrip'], 'Verified',
            'Authenticated camera configuration and control connection.', [], true);

        $onvif = $observed['onvif'] ?? [];
        $onvifPort = isset($onvif['url']) ? (int) (parse_url($onvif['url'], PHP_URL_PORT) ?: 80) : null;
        foreach ($observed['http'] ?? [] as $port => $web) {
            if ((int) $port === $onvifPort && (int) $port !== ($ports['http'] ?? 80)) continue;
            $tls = str_starts_with($web['url'], 'https:');
            $add($tls ? 'https' : 'http', $tls ? 'HTTPS web interface' : 'HTTP web interface', 'TCP', (int) $port,
                'HTTP response ' . $web['status'], $tls ? 'Encrypted connection; certificate trust was not checked.'
                    : 'Web interface over unencrypted HTTP. Older camera pages may require the native CMS/ActiveX client.',
                [['label' => 'Web address', 'url' => $web['url']]], true);
        }
        foreach (['http', 'https'] as $id) {
            $port = $ports[$id] ?? null;
            if ($port === null || isset($used[$port])) continue;
            $reported = isset($network['NetCommon'][$id === 'http' ? 'HttpPort' : 'SSLPort']) || isset($camera['access']['ports'][$id]);
            $name = $id === 'https' ? 'TLS / HTTPS candidate port' : 'HTTP' . ($reported ? ' configured port' : ' candidate port');
            $add($id, $name, 'TCP', $port,
                ($observed['tcp'][$port] ?? false) ? 'TCP open; web protocol unverified' : 'Not reachable',
                'A port value in the camera settings does not prove that a web service is enabled. '
                . ($id === 'https' ? 'The SSL port is a discovery candidate; no HTTPS response was verified.' : ''), [], true);
        }
        if (isset($observed['snapshot'])) {
            $add('snapshot_http', 'HTTP JPEG snapshot', 'TCP', (int) (parse_url($observed['snapshot'], PHP_URL_PORT) ?: 80),
                'JPEG response verified without login', 'Returns a still image. No credentials were sent for this successful request.',
                [['label' => 'Snapshot address', 'url' => $observed['snapshot']]]);
            $points[array_key_last($points)]['credentialNote'] = 'No login was required for the tested snapshot request.';
        }
        $rtspPort = $ports['rtsp'] ?? 554;
        $streams = array_map(static fn ($s) => ['label' => 'Profile ' . $s['profile'] . ' (camera-advertised)', 'url' => $s['url']], $observed['streams'] ?? []);
        $rtspState = ($observed['rtsp'] ?? false) ? 'RTSP response verified'
            : (($observed['tcp'][$rtspPort] ?? false) ? 'TCP open; RTSP unverified' : 'Not reachable');
        if (($network['RTSP']['IsServer'] ?? null) === false) $rtspState .= ' / disabled in camera settings';
        $add('rtsp', 'RTSP video / audio', 'TCP', $rtspPort, $rtspState,
            'Use a media client such as VLC. Stream URLs below are advertised by ONVIF; playback was not tested. '
            . 'Replace {login} and {password} placeholders with the account fields. '
            . ($streams === [] ? 'No stream paths were returned; the base address alone may not play video.' : ''),
            array_merge([['label' => 'Base address', 'url' => 'rtsp://' . $address . ':' . $rtspPort . '/']], $streams), true);
        if ($onvif !== []) {
            $auth = $network['OnvifPwdCheckout']['Enable'] ?? null;
            $authNote = match ($auth) {true => 'Password checking is enabled in camera settings.', false => 'Password checking is disabled in camera settings.', default => 'Authentication policy was not reported.'};
            $endpoints = [['label' => 'Device service', 'url' => $onvif['url']]];
            foreach ($onvif['services'] as $service) {
                $endpoints[] = ['label' => $service['namespace'], 'url' => $service['url']];
            }
            $add('onvif', 'ONVIF', 'TCP', $onvifPort, 'GetServices verified',
                $authNote . ' Service discovery answered without credentials; other operations may still require them. '
                . 'Advertised PTZ services do not prove that the camera has motors.', $endpoints, true);
        }
        if ($observed['discovery'] ?? false) $add('discovery', 'ONVIF WS-Discovery', 'UDP', 3702,
            'Discovery response verified', 'Local device discovery, not a login or video endpoint.');
        $udp = $network['NetCommon']['UDPPort'] ?? null;
        if (is_int($udp) && $udp > 0 && $udp <= 65535) $add('dvrip_udp', 'Vendor UDP service', 'UDP', $udp,
            'Camera-reported; not probed', 'Vendor UDP port from network settings. Availability and authentication were not tested.');
        foreach ($observed['tcp'] ?? [] as $port => $open) {
            if (!$open || isset($used[$port])) continue;
            $hint = [21 => 'FTP candidate', 22 => 'SSH candidate', 23 => 'Telnet candidate'][$port] ?? 'Unidentified service';
            $add('tcp_' . $port, $hint, 'TCP', (int) $port, 'TCP open; protocol unknown',
                'A connection was accepted, but the service was not identified. Do not assume it uses the camera account.');
        }
        $nat = $network['Nat'] ?? null;
        if (is_array($nat) && array_key_exists('NatEnable', $nat)) {
            $points[] = ['id' => 'cloud', 'name' => 'Vendor cloud / NAT', 'transport' => 'Outbound',
                'host' => $nat['Addr'] ?? '', 'port' => $nat['Port'] ?? null,
                'state' => $nat['NatEnable'] ? 'Enabled in camera settings; reachability not tested' : 'Disabled in camera settings',
                'note' => 'Remote access through the vendor app/cloud. This is an outbound service, not a local listening port.',
                'username' => null, 'password' => null, 'credentialNote' => 'Vendor cloud account is separate and is not available from this camera configuration.',
                'endpoints' => []];
        }
        // Only listening inbound endpoints belong to the access manager.
        $points = array_values(array_filter($points, static fn ($point) =>
            !in_array($point['id'], ['cloud', 'discovery', 'dvrip_udp'], true)
            && !str_starts_with($point['state'], 'Not reachable')));
        foreach ($points as &$point) {
            $scheme = $point['id'] === 'dvrip' ? 'dvrip' : ($point['id'] === 'rtsp' ? 'rtsp' : 'tcp');
            $point['url'] = $point['endpoints'][0]['url'] ?? $scheme . '://' . $address . ':' . $point['port'] . '/';
            $point['accountManager'] = $point['id'] === 'dvrip';
            if ($point['id'] === 'rtsp') {
                $point['note'] = 'RTSP stream access. Account controls appear when its account-management route has been verified for this camera.';
            }
            if (isset($camera['access']['notes'][$point['id']])) $point['note'] .= ' ' . $camera['access']['notes'][$point['id']];
        }
        unset($point);
        $unreachable = array_keys(array_filter($observed['tcp'] ?? [], static fn ($open) => !$open));
        return ['host' => $host, 'checkedAt' => gmdate('c'), 'points' => $points, 'unreachablePorts' => $unreachable,
            'note' => 'Manage accounts used to access this camera. Save applies changes to the camera; its result explains how they were checked. '
                . 'Services with no working credential-change API show an explanation instead of an editable password.'];
    }
}
