<?php

declare(strict_types=1);

use Cam\Config;
use Cam\LiveStream;

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/LiveStream.php';

$process = null;
$pipes = [];
$lock = null;

try {
    $cameraId = $_GET['camera'] ?? null;
    $action = $_GET['action'] ?? null;
    if (!is_string($cameraId) || $cameraId === '') {
        throw new InvalidArgumentException('Camera is required.');
    }
    if (!is_string($action) || !in_array($action, ['info', 'stream'], true)) {
        throw new InvalidArgumentException('Unknown live-video action.');
    }

    $live = new LiveStream(Config::load(dirname(__DIR__, 2) . '/config.php'));
    $live->assertRuntimeAvailable();
    if ($action === 'info') {
        $command = $live->probeCommand($cameraId);
    } else {
        $codec = $_GET['codec'] ?? null;
        $audioValue = $_GET['audio'] ?? '0';
        if (!is_string($codec)) {
            throw new InvalidArgumentException('Video codec is required.');
        }
        if (!is_string($audioValue) || !in_array($audioValue, ['0', '1'], true)) {
            throw new InvalidArgumentException('Audio mode is invalid.');
        }
        $lock = fopen(sys_get_temp_dir() . '/alina-cam-live-' . $live->lockKey($cameraId) . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('This camera already has a live viewer. Stop the other player first.');
        }
        $command = $live->streamCommand($cameraId, $codec, $audioValue === '1');
    }

    // Argument arrays bypass the shell. Camera credentials and FFmpeg stderr are never returned.
    $process = proc_open(
        $command,
        [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start the live-video reader.');
    }

    stream_set_blocking($pipes[1], false);
    set_time_limit(14400);
    $deadline = microtime(true) + ($action === 'info' ? 8 : 14350);
    $started = false;
    $output = '';

    while (microtime(true) < $deadline) {
        $read = [$pipes[1]];
        $write = null;
        $except = null;
        $ready = stream_select($read, $write, $except, 0, 200000);
        if ($ready === false) {
            break;
        }
        if ($ready > 0) {
            $chunk = fread($pipes[1], 65536);
            if ($chunk !== false && $chunk !== '') {
                if ($action === 'info') {
                    $output .= $chunk;
                    if (strlen($output) > 65536) {
                        throw new RuntimeException('The camera returned invalid stream metadata.');
                    }
                } else {
                    if (!$started) {
                        ignore_user_abort(true);
                        ini_set('zlib.output_compression', '0');
                        while (ob_get_level() > 0) {
                            ob_end_clean();
                        }
                        header('Content-Type: video/mp4');
                        header('Cache-Control: no-store, max-age=0');
                        header('X-Accel-Buffering: no');
                        $started = true;
                    }
                    echo $chunk;
                    flush();
                    if (connection_aborted()) {
                        break;
                    }
                }
            }
        }
        if (feof($pipes[1])) {
            break;
        }
    }

    if ($action === 'info') {
        $data = json_decode($output, true);
        $streams = is_array($data['streams'] ?? null) ? $data['streams'] : [];
        $video = null;
        $audio = null;
        foreach ($streams as $stream) {
            if (!is_array($stream)) continue;
            if ($video === null && ($stream['codec_type'] ?? null) === 'video') $video = $stream;
            if ($audio === null && ($stream['codec_type'] ?? null) === 'audio') $audio = $stream;
        }
        if (!is_array($video) || !in_array($video['codec_name'] ?? null, ['h264', 'hevc'], true)) {
            throw new RuntimeException('The camera did not provide a supported video stream.');
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        echo json_encode([
            'ok' => true,
            'video' => $video,
            'audio' => [
                'available' => is_array($audio),
                'codec_name' => is_array($audio) && is_string($audio['codec_name'] ?? null)
                    ? $audio['codec_name']
                    : null,
            ],
        ], JSON_THROW_ON_ERROR);
    } elseif (!$started) {
        throw new RuntimeException('The camera did not produce live video.');
    }
} catch (Throwable $error) {
    if (!headers_sent()) {
        http_response_code($error instanceof InvalidArgumentException ? 422 : 502);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        $message = $error instanceof InvalidArgumentException || $error instanceof RuntimeException
            ? $error->getMessage()
            : 'Live video is unavailable.';
        echo json_encode(['ok' => false, 'error' => $message], JSON_THROW_ON_ERROR);
    }
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($process);
    }
    if (is_resource($lock)) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
