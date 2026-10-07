<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';

try {
    $config = \Cam\Config::load(dirname(__DIR__, 2) . '/config.php');
    $id = $_GET['camera'] ?? '';
    if (!is_string($id)) throw new InvalidArgumentException('Unknown camera.');
    $camera = $config->camera($id);
    $host = $camera['host'];
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        throw new RuntimeException('Snapshot requires an IPv4 camera host.');
    }
    $url = "http://{$host}/webcapture.jpg?command=snap&channel=0";
    $handle = curl_init($url);
    if ($handle === false) throw new RuntimeException('Snapshot unavailable.');
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXFILESIZE => 4 * 1024 * 1024,
    ]);
    $jpeg = curl_exec($handle);
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    $imageInfo = is_string($jpeg) ? @getimagesizefromstring($jpeg) : false;
    if ($status !== 200 || !is_string($jpeg) || strlen($jpeg) > 4 * 1024 * 1024
        || ($imageInfo[2] ?? null) !== IMAGETYPE_JPEG) {
        throw new RuntimeException('Camera did not return a JPEG snapshot.');
    }
    header('Content-Type: image/jpeg');
    header('Cache-Control: no-store');
    echo $jpeg;
} catch (Throwable $exception) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Camera snapshot unavailable.';
}
