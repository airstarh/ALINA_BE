<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/CameraSnapshot.php';

try {
    $config = \Cam\Config::load(dirname(__DIR__, 2) . '/config.php');
    $id = $_GET['camera'] ?? '';
    if (!is_string($id)) throw new InvalidArgumentException('Unknown camera.');
    $jpeg = (new \Cam\CameraSnapshot($config))->capture($id);
    header('Content-Type: image/jpeg');
    header('Cache-Control: no-store');
    echo $jpeg;
} catch (Throwable $exception) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Camera snapshot unavailable.';
}
