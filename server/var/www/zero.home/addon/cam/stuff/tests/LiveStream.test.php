<?php

declare(strict_types=1);

use Cam\Config;
use Cam\LiveStream;

require_once dirname(__DIR__) . '/php/Config.php';
require_once dirname(__DIR__) . '/php/LiveStream.php';

function liveStreamConfigFixture(): string
{
    $path = tempnam(sys_get_temp_dir(), 'cam-live-config-');
    if ($path === false) {
        throw new RuntimeException('could not create live-stream configuration fixture');
    }

    $config = [
        'default' => 'balcon',
        'cameras' => [
            'balcon' => [
                'name' => 'Balcon',
                'host' => '192.0.2.21',
                'access' => [
                    'ports' => ['rtsp' => 8554],
                    'credentials' => [
                        'rtsp' => ['username' => 'viewer name', 'password' => 'p@ss/word'],
                    ],
                ],
                'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => 'fallback'],
            ],
            'door' => [
                'name' => 'Door',
                'host' => '192.0.2.22',
                'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => 'secret'],
            ],
        ],
    ];
    file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";\n");

    return $path;
}

test('live stream builds a Balcon HEVC stream-copy pipeline', function (): void {
    $path = liveStreamConfigFixture();
    try {
        $live = new LiveStream(Config::load($path), '/opt/ffmpeg', '/opt/ffprobe');
        $probe = $live->probeCommand('balcon');
        $stream = $live->streamCommand('balcon', 'hevc');

        assertSameValue('/opt/ffprobe', $probe[0]);
        assertTrueValue(in_array('rtsp://viewer%20name:p%40ss%2Fword@192.0.2.21:8554/', $probe, true));
        assertSameValue('/opt/ffmpeg', $stream[0]);
        assertTrueValue(in_array('copy', $stream, true));
        assertTrueValue(in_array('hvc1', $stream, true));
        assertTrueValue(in_array('frag_keyframe+empty_moov+default_base_moof', $stream, true));
        assertTrueValue(!in_array('libx264', $stream, true));
    } finally {
        unlink($path);
    }
});

test('live stream builds a configured Door stream with DVRIP credential fallback', function (): void {
    $path = liveStreamConfigFixture();
    try {
        $live = new LiveStream(Config::load($path), '/opt/ffmpeg', '/opt/ffprobe');
        $probe = $live->probeCommand('door');
        $stream = $live->streamCommand('door', 'h264');

        assertTrueValue(in_array('rtsp://admin:secret@192.0.2.22:554/', $probe, true));
        assertTrueValue(in_array('rtsp://admin:secret@192.0.2.22:554/', $stream, true));
        assertTrueValue(!in_array('hvc1', $stream, true));
    } finally {
        unlink($path);
    }
});
