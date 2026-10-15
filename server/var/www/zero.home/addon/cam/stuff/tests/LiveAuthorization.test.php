<?php

declare(strict_types=1);

use Cam\Config;
use Cam\LiveAuthorization;
use Cam\TalkToken;

require_once dirname(__DIR__) . '/php/Config.php';
require_once dirname(__DIR__) . '/php/TalkToken.php';
require_once dirname(__DIR__) . '/php/LiveStream.php';
$authorizationFile = dirname(__DIR__) . '/php/LiveAuthorization.php';
if (is_file($authorizationFile)) {
    require_once $authorizationFile;
}

function liveAuthorizationFixture(): string
{
    $path = tempnam(sys_get_temp_dir(), 'cam-live-auth-');
    if ($path === false) throw new RuntimeException('could not create authorization fixture');
    $config = [
        'default' => 'balcon',
        'cameras' => [
            'balcon' => [
                'name' => 'Balcon',
                'host' => '192.0.2.21',
                'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => 'camera-secret'],
            ],
            'door' => [
                'name' => 'Door',
                'host' => '192.0.2.22',
                'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => 'door-secret'],
            ],
        ],
    ];
    file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";\n");
    return $path;
}

test('live authorization accepts a valid camera-bound relay token', function (): void {
    assertTrueValue(class_exists(LiveAuthorization::class), 'LiveAuthorization is not implemented');
    $path = liveAuthorizationFixture();
    try {
        $tokens = new TalkToken(str_repeat('s', 32), 30);
        $authorization = new LiveAuthorization(Config::load($path), $tokens);
        $issued = $tokens->issue('balcon', 1000);

        $authorization->authorize([
            'camera' => 'balcon',
            'src' => 'alina-balcon',
            'token' => $issued['token'],
        ], 1001);
        assertTrueValue(true);
    } finally {
        unlink($path);
    }
});

test('live authorization rejects invalid token camera stream and expiry combinations', function (): void {
    assertTrueValue(class_exists(LiveAuthorization::class), 'LiveAuthorization is not implemented');
    $path = liveAuthorizationFixture();
    try {
        $tokens = new TalkToken(str_repeat('s', 32), 30);
        $authorization = new LiveAuthorization(Config::load($path), $tokens);
        $balcon = $tokens->issue('balcon', 1000)['token'];
        $unknown = $tokens->issue('unknown', 1000)['token'];
        $cases = [
            [['camera' => 'balcon', 'src' => 'alina-balcon', 'token' => 'malformed'], 1001],
            [['camera' => 'balcon', 'src' => 'alina-balcon', 'token' => $balcon], 1030],
            [['camera' => 'door', 'src' => 'alina-door', 'token' => $balcon], 1001],
            [['camera' => 'balcon', 'src' => 'alina-door', 'token' => $balcon], 1001],
            [['camera' => 'unknown', 'src' => 'alina-unknown', 'token' => $unknown], 1001],
            [['camera' => 'balcon', 'src' => 'alina-balcon'], 1001],
        ];

        foreach ($cases as [$query, $now]) {
            $error = assertThrows(
                static fn () => $authorization->authorize($query, $now),
                InvalidArgumentException::class
            );
            assertSameValue('Live stream authorization failed.', $error->getMessage());
            assertSameValue(false, str_contains($error->getMessage(), 'secret'));
        }
    } finally {
        unlink($path);
    }
});

test('live authorization extracts WebSocket query from the original Nginx request URI', function (): void {
    assertSameValue(
        ['camera' => 'balcon', 'src' => 'alina-balcon', 'token' => 'signed.token'],
        LiveAuthorization::requestQuery([
            'HTTP_X_ORIGINAL_URI' => '/cam-stream/api/ws?camera=balcon&src=alina-balcon&token=signed.token',
        ], ['ignored' => 'subrequest'])
    );
});
