<?php

declare(strict_types=1);

use Cam\Config;
use Cam\Go2rtcClient;
use Cam\LiveRecorder;
use Cam\LiveStream;

foreach (['Config.php', 'Go2rtcClient.php', 'LiveStream.php'] as $file) {
    require_once dirname(__DIR__) . '/php/' . $file;
}
$recorderClass = dirname(__DIR__) . '/php/LiveRecorder.php';
if (is_file($recorderClass)) {
    require_once $recorderClass;
}

function liveRecorderFixture(int|string|null $lengthMax = 300): array
{
    $root = sys_get_temp_dir() . '/cam-recorder-' . bin2hex(random_bytes(6));
    $storage = $root . '/storage';
    $state = $root . '/state';
    mkdir($root);
    mkdir($storage);
    mkdir($state);
    $config = [
        'default' => 'balcon',
        'record' => array_filter(
            ['storage' => $storage, 'lengthMax' => $lengthMax],
            static fn (mixed $value): bool => $value !== null
        ),
        'cameras' => [
            'balcon' => [
                'name' => 'Balcon',
                'host' => '192.0.2.21',
                'live' => ['rtsp_transport' => 'udp'],
                'access' => [
                    'ports' => ['rtsp' => 8554],
                    'credentials' => ['rtsp' => ['username' => 'viewer', 'password' => 'secret']],
                ],
                'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => 'fallback'],
            ],
        ],
    ];
    $path = $root . '/config.php';
    file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";\n");

    return [$root, $storage, $state, $path];
}

function removeRecorderFixture(string $root): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
}

test('live recorder saves the shared go2rtc MP4 stream under the configured timestamp name', function (): void {
    if (!class_exists(LiveRecorder::class)) {
        throw new RuntimeException('Cam\\LiveRecorder does not exist');
    }
    [$root, $storage, $state, $path] = liveRecorderFixture('300');
    $openedUrl = null;
    $relay = new Go2rtcClient('http://relay.test/cam-stream', static fn (): array => ['status' => 200, 'body' => '']);
    $open = static function (string $url) use (&$openedUrl) {
        $openedUrl = $url;
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'fragmented-mp4');
        rewind($stream);
        return $stream;
    };

    try {
        $config = Config::load($path);
        $recorder = new LiveRecorder(
            $config,
            new LiveStream($config),
            $relay,
            $state,
            'http://relay.test/cam-stream',
            $open,
            static fn (): DateTimeImmutable => new DateTimeImmutable('2026-12-31 22:55:00')
        );

        $session = $recorder->start('balcon');
        assertSameValue(true, $recorder->status('balcon')['active']);
        assertSameValue('2026-12-31---22-55---balcon.mp4', $session->metadata()['filename']);
        $session->capture();

        assertSameValue('fragmented-mp4', file_get_contents($storage . '/2026-12-31---22-55---balcon.mp4'));
        assertSameValue(false, $recorder->status('balcon')['active']);
        $query = [];
        parse_str((string) parse_url((string) $openedUrl, PHP_URL_QUERY), $query);
        assertSameValue('/cam-stream/api/stream.mp4', parse_url((string) $openedUrl, PHP_URL_PATH));
        assertSameValue('alina-balcon', $query['src'] ?? null);
        assertSameValue('flac', $query['mp4'] ?? null);
        assertSameValue('300', $query['duration'] ?? null);
    } finally {
        removeRecorderFixture($root);
    }
});

test('live recorder second action requests stop without starting another recording', function (): void {
    if (!class_exists(LiveRecorder::class)) {
        throw new RuntimeException('Cam\\LiveRecorder does not exist');
    }
    [$root, , $state, $path] = liveRecorderFixture(300);
    $relay = new Go2rtcClient('http://relay.test/cam-stream', static fn (): array => ['status' => 200, 'body' => '']);
    $open = static function () {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'data that must not be read after an immediate stop');
        rewind($stream);
        return $stream;
    };

    try {
        $config = Config::load($path);
        $recorder = new LiveRecorder($config, new LiveStream($config), $relay, $state, 'http://relay.test', $open);
        $session = $recorder->start('balcon');

        assertSameValue(true, $recorder->stop('balcon')['stopping']);
        $session->capture();
        assertSameValue(false, $recorder->status('balcon')['active']);
        assertSameValue([], glob(dirname($path) . '/storage/*.mp4') ?: []);
    } finally {
        removeRecorderFixture($root);
    }
});
