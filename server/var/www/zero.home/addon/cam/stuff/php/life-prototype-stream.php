<?php
declare(strict_types=1);

// Temporary feasibility probe, not the production Life/PTZ service.
require_once __DIR__ . '/Config.php';

$process = null;
$pipes = [];
$lock = null;
try {
    $config = Cam\Config::load(dirname(__DIR__, 2) . '/config.php');
    $id = $_GET['camera'] ?? $config->defaultCameraId();
    if (!is_string($id)) throw new RuntimeException('Unknown camera.');
    $camera = $config->camera($id);
    $action = $_GET['action'] ?? 'info';
    if (!in_array($action, ['info', 'stream'], true)) throw new RuntimeException('Unknown action.');
    $binary = $action === 'info' ? '/usr/bin/ffprobe' : '/usr/bin/ffmpeg';
    if (!is_executable($binary) || !function_exists('proc_open')) {
        throw new RuntimeException('FFmpeg/FFprobe is not available in this PHP runtime. No software was installed.');
    }
    $host = $camera['host'];
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) throw new RuntimeException('Prototype requires an IPv4 camera host.');
    $credentials = $camera['access']['credentials']['rtsp'] ?? $camera['dvrip'];
    $port = $camera['access']['ports']['rtsp'] ?? 554;
    $url = 'rtsp://' . rawurlencode($credentials['username']) . ':' . rawurlencode($credentials['password']) . '@' . $host . ':' . $port . '/';
    if ($action === 'info') {
        $command = [$binary, '-v', 'quiet', '-rtsp_transport', 'tcp', '-rw_timeout', '5000000',
            '-select_streams', 'v:0', '-show_entries', 'stream=codec_name,profile,level,width,height', '-of', 'json', $url];
    } else {
        $lock = fopen(sys_get_temp_dir() . '/alina-life-prototype-' . hash('sha256', $host) . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('This camera already has a prototype stream open. Stop the other player first.');
        $codec = $_GET['codec'] ?? '';
        if (!in_array($codec, ['h264', 'hevc'], true)) throw new RuntimeException('Unsupported prototype codec.');
        $command = [$binary, '-nostdin', '-hide_banner', '-loglevel', 'error', '-rtsp_transport', 'tcp',
            '-timeout', '5000000', '-fflags', '+genpts', '-i', $url, '-map', '0:v:0', '-an', '-c:v', 'copy'];
        if ($codec === 'hevc') array_push($command, '-tag:v', 'hvc1');
        array_push($command, '-t', '90', '-movflags', 'frag_keyframe+empty_moov+default_base_moof',
            '-frag_duration', '100000', '-flush_packets', '1', '-f', 'mp4', 'pipe:1');
    }
    // argv array bypasses the shell. Never return camera credentials or FFmpeg stderr.
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start the prototype reader.');
    stream_set_blocking($pipes[1], false);
    set_time_limit(110);
    $deadline = microtime(true) + ($action === 'info' ? 8 : 95);
    $started = false;
    $output = '';
    while (microtime(true) < $deadline) {
        $read = [$pipes[1]]; $write = null; $except = null;
        $ready = stream_select($read, $write, $except, 0, 200000);
        if ($ready === false) break;
        if ($ready > 0) {
            $chunk = fread($pipes[1], 65536);
            if ($chunk !== false && $chunk !== '') {
                if ($action === 'info') {
                    $output .= $chunk;
                    if (strlen($output) > 65536) throw new RuntimeException('Invalid stream metadata.');
                } else {
                    if (!$started) {
                        ignore_user_abort(true);
                        ini_set('zlib.output_compression', '0');
                        while (ob_get_level() > 0) ob_end_clean();
                        header('Content-Type: video/mp4');
                        header('Cache-Control: no-store');
                        header('X-Accel-Buffering: no');
                        $started = true;
                    }
                    echo $chunk;
                    flush();
                    if (connection_aborted()) break;
                }
            }
        }
        if (feof($pipes[1])) break;
    }
    if ($action === 'info') {
        $data = json_decode($output, true);
        $video = $data['streams'][0] ?? null;
        if (!is_array($video) || !in_array($video['codec_name'] ?? '', ['h264', 'hevc'], true)) throw new RuntimeException('No supported RTSP video was received.');
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => true, 'video' => $video, 'durationLimit' => 90], JSON_THROW_ON_ERROR);
    } elseif (!$started) {
        throw new RuntimeException('The camera did not produce fragmented MP4 video.');
    }
} catch (Throwable $error) {
    if (!headers_sent()) {
        http_response_code(502);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => false, 'error' => $error instanceof RuntimeException ? $error->getMessage() : 'Prototype stream unavailable.']);
    }
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        proc_close($process);
    }
    if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
}
