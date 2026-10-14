<?php

declare(strict_types=1);

use Cam\OnvifPtz;
use Cam\DvripException;

$class = dirname(__DIR__) . '/php/OnvifPtz.php';
if (!is_file($class)) throw new RuntimeException('ONVIF PTZ client does not exist');
require_once $class;

test('onvif_ptz_maps_physical_directions_and_always_stops', function (): void {
    $calls = [];
    $slept = [];
    $request = static function (string $url, string $namespace, string $operation, string $content) use (&$calls): string {
        $calls[] = compact('url', 'namespace', 'operation', 'content');
        return $operation === 'GetProfiles'
            ? '<trt:GetProfilesResponse><trt:Profiles token="000"/></trt:GetProfilesResponse>'
            : '<tptz:' . $operation . 'Response/>';
    };
    $ptz = new OnvifPtz(
        'http://192.0.2.1:8899/onvif/Media',
        'http://192.0.2.1:8899/onvif/PTZ',
        ['username' => 'admin', 'password' => 'secret'],
        $request,
        static function (int $microseconds) use (&$slept): void { $slept[] = $microseconds; },
    );

    $ptz->pulse('up-left', 'medium', 180);

    assertSameValue(['GetProfiles', 'ContinuousMove', 'Stop'], array_column($calls, 'operation'));
    assertTrueValue(str_contains($calls[1]['content'], 'ProfileToken>000<'));
    assertTrueValue(str_contains($calls[1]['content'], 'x="-0.5" y="0.5"'));
    assertTrueValue(!str_contains($calls[1]['content'], '<tt:Zoom'));
    assertSameValue([180000], $slept);
    assertTrueValue(str_contains($calls[2]['content'], '<tptz:PanTilt>true</tptz:PanTilt>'));
    assertTrueValue(str_contains($calls[2]['content'], '<tptz:Zoom>true</tptz:Zoom>'));
});

test('onvif_ptz_zoom_uses_zoom_axis_and_stop_runs_after_move_failure', function (): void {
    $calls = [];
    $request = static function (string $url, string $namespace, string $operation, string $content) use (&$calls): string {
        $calls[] = compact('operation', 'content');
        if ($operation === 'GetProfiles') return '<Profiles token="profile-a"/>';
        if ($operation === 'ContinuousMove') throw new RuntimeException('move failed');
        return '<StopResponse/>';
    };
    $ptz = new OnvifPtz('http://camera/Media', 'http://camera/PTZ', [], $request, static function (): void {});

    $error = assertThrows(static fn () => $ptz->pulse('zoom-in', 'high', 120), RuntimeException::class);

    assertSameValue('move failed', $error->getMessage());
    assertSameValue(['GetProfiles', 'ContinuousMove', 'Stop'], array_column($calls, 'operation'));
    assertTrueValue(!str_contains($calls[1]['content'], '<tt:PanTilt'));
    assertTrueValue(str_contains($calls[1]['content'], '<tt:Zoom x="1"'));
});

test('onvif_ptz_rejects_unbounded_or_unknown_pulses_before_io', function (): void {
    $calls = 0;
    $ptz = new OnvifPtz('http://camera/Media', 'http://camera/PTZ', [],
        static function () use (&$calls): string { $calls++; return ''; }, static function (): void {});

    assertThrows(static fn () => $ptz->pulse('spin', 'medium', 180), InvalidArgumentException::class);
    assertThrows(static fn () => $ptz->pulse('left', 'turbo', 180), InvalidArgumentException::class);
    assertThrows(static fn () => $ptz->pulse('left', 'medium', 1000), InvalidArgumentException::class);
    assertSameValue(0, $calls);
});

test('onvif_ptz_reports_stop_failure_with_a_stable_public_code', function (): void {
    $request = static function (string $url, string $namespace, string $operation): string {
        if ($operation === 'GetProfiles') return '<Profiles token="000"/>';
        if ($operation === 'Stop') throw new RuntimeException('fixture stop failure');
        return '<ContinuousMoveResponse/>';
    };
    $ptz = new OnvifPtz('http://camera/Media', 'http://camera/PTZ', [], $request, static function (): void {});
    $error = assertThrows(static fn () => $ptz->pulse('right', 'low', 80), DvripException::class);
    assertSameValue('ptz_stop_failed', $error->publicCode());
});

