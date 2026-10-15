<?php

declare(strict_types=1);

use Cam\Go2rtcClient;

$clientFile = dirname(__DIR__) . '/php/Go2rtcClient.php';
if (is_file($clientFile)) {
    require_once $clientFile;
}

test('go2rtc client upserts one URL-encoded stream through the private API', function (): void {
    assertTrueValue(class_exists(Go2rtcClient::class), 'Go2rtcClient is not implemented');

    $requests = [];
    $client = new Go2rtcClient(
        'http://go2rtc:1984/cam-stream/',
        static function (string $method, string $url, float $timeout) use (&$requests): array {
            $requests[] = compact('method', 'url', 'timeout');
            return ['status' => 204, 'body' => ''];
        }
    );

    $source = 'rtsp://viewer:p%40ss@192.0.2.21:554/#transport=udp';
    $client->upsert('alina-balcon', $source);
    $client->upsert('alina-balcon', $source);

    assertSameValue(2, count($requests));
    assertSameValue('PUT', $requests[0]['method']);
    assertSameValue(2.0, $requests[0]['timeout']);
    assertSameValue('/cam-stream/api/streams', parse_url($requests[0]['url'], PHP_URL_PATH));
    parse_str((string) parse_url($requests[0]['url'], PHP_URL_QUERY), $query);
    assertSameValue('alina-balcon', $query['name'] ?? null);
    assertSameValue($source, $query['src'] ?? null);
});

test('go2rtc client rejects unsafe names before making a request', function (): void {
    assertTrueValue(class_exists(Go2rtcClient::class), 'Go2rtcClient is not implemented');

    $called = false;
    $client = new Go2rtcClient(
        request: static function () use (&$called): array {
            $called = true;
            return ['status' => 204, 'body' => ''];
        }
    );

    assertThrows(
        static fn () => $client->upsert('../camera', 'rtsp://admin:secret@192.0.2.21/'),
        InvalidArgumentException::class
    );
    assertSameValue(false, $called);
});

test('go2rtc client reports sanitized transport and HTTP failures', function (): void {
    assertTrueValue(class_exists(Go2rtcClient::class), 'Go2rtcClient is not implemented');

    foreach ([
        static fn (): array => ['status' => 500, 'body' => 'rtsp://admin:secret@camera/'],
        static function (): never { throw new RuntimeException('rtsp://admin:secret@camera/'); },
    ] as $request) {
        $client = new Go2rtcClient(request: $request);
        $error = assertThrows(
            static fn () => $client->upsert('alina-balcon', 'rtsp://admin:secret@192.0.2.21/'),
            RuntimeException::class
        );
        assertSameValue(false, str_contains($error->getMessage(), 'secret'));
        assertSameValue('Camera relay is unavailable.', $error->getMessage());
    }
});
