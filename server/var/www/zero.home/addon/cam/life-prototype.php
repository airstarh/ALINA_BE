<?php
declare(strict_types=1);
require_once __DIR__ . '/stuff/php/Config.php';
$config = Cam\Config::load(__DIR__ . '/config.php');
$selectedCamera = $_GET['camera'] ?? $config->defaultCameraId();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Life — native video experiment</title>
    <link rel="stylesheet" href="./stuff/css/app.css?v=20261009-9">
    <style>
        .prototype { max-width: 1200px; margin: 2rem auto; padding: 1rem; }
        .prototype video { display: block; width: 100%; max-height: 75vh; background: #000; margin: 1rem 0; }
        .prototype-controls { display: flex; flex-wrap: wrap; gap: .7rem; align-items: center; }
        .prototype select { background: #000; }
    </style>
</head>
<body><main class="prototype">
    <h1>Life — native video experiment</h1>
    <p>Native browser MSE playback. Video only; compressed video is copied, not re-encoded. No PTZ or camera changes.</p>
    <div class="prototype-controls">
        <label for="camera">Camera</label>
        <select id="camera">
        <?php foreach ($config->publicCameras() as $camera): ?>
            <option value="<?= htmlspecialchars($camera['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $camera['id'] === $selectedCamera ? 'selected' : '' ?>><?= htmlspecialchars($camera['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
        </select>
        <button id="start" type="button">Start</button>
        <button id="stop" type="button" disabled>Stop</button>
    </div>
    <p id="status" role="status" aria-live="polite">Press Start. Each experiment runs for at most 90 seconds.</p>
    <video id="video" autoplay muted playsinline controls></video>
    <p>For feedback: did video appear, was it smooth, and approximately how delayed was it? If unsupported, copy the status message.</p>
    <script type="module" src="./stuff/js/life-prototype.js?v=1"></script>
</main></body></html>
