<?php

declare(strict_types=1);

use Cam\CameraSnapshot;
use Cam\Config;

require_once dirname(__DIR__) . '/php/Config.php';
$cameraSnapshotClass = dirname(__DIR__) . '/php/CameraSnapshot.php';
if (is_file($cameraSnapshotClass)) {
    require_once $cameraSnapshotClass;
}

function cameraSnapshotFixture(): array
{
    $root = sys_get_temp_dir() . '/cam-snapshot-' . bin2hex(random_bytes(6));
    $storage = $root . '/storage';
    mkdir($root);
    mkdir($storage);
    $config = [
        'default' => 'balcon',
        'record' => ['storage' => $storage, 'lengthMax' => 300],
        'cameras' => [
            'balcon' => [
                'name' => 'Balcon',
                'host' => '192.0.2.21',
                'access' => ['ports' => ['http' => 8080]],
                'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => ''],
            ],
        ],
    ];
    $path = $root . '/config.php';
    file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";\n");

    return [$root, $storage, $path];
}

function removeCameraSnapshotFixture(string $root): void
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

test('camera snapshot saves the native JPEG under a microsecond Unix timestamp name', function (): void {
    if (!class_exists(CameraSnapshot::class)) {
        throw new RuntimeException('Cam\\CameraSnapshot does not exist');
    }
    [$root, $storage, $path] = cameraSnapshotFixture();
    $jpeg = base64_decode(
        '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////'
        . '2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB'
        . '/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAEf/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/'
        . 'aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBAB'
        . 'AAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEA'
        . 'AAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EH//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EH//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/'
        . '9oACAEBAAE/EH//2Q==',
        true
    );
    $requestedUrl = null;

    try {
        $snapshot = new CameraSnapshot(
            Config::load($path),
            static function (string $url) use (&$requestedUrl, $jpeg): array {
                $requestedUrl = $url;
                return ['status' => 200, 'body' => $jpeg];
            },
            static fn (): DateTimeImmutable => new DateTimeImmutable('2026-12-31 22:55:00.123456+00:00')
        );

        $saved = $snapshot->save('balcon');

        assertSameValue('http://192.0.2.21:8080/webcapture.jpg?command=snap&channel=0', $requestedUrl);
        assertSameValue('2026-12-31---22-55---1798757700123456---balcon.jpg', $saved['filename']);
        assertSameValue($jpeg, file_get_contents($storage . '/' . $saved['filename']));
    } finally {
        removeCameraSnapshotFixture($root);
    }
});
