<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;
use RuntimeException;

final class LiveStream
{
    public function __construct(
        private Config $config,
        private string $ffmpeg = '/usr/bin/ffmpeg',
        private string $ffprobe = '/usr/bin/ffprobe',
    ) {
    }

    public function assertRuntimeAvailable(): void
    {
        if (!is_executable($this->ffmpeg) || !is_executable($this->ffprobe)
            || !function_exists('proc_open')) {
            throw new RuntimeException('FFmpeg/FFprobe is unavailable in the PHP runtime.');
        }
    }

    public function probeCommand(string $cameraId): array
    {
        $transport = $this->transport($cameraId);

        return [
            $this->ffprobe,
            '-v', 'quiet',
            '-rtsp_transport', $transport,
            '-rw_timeout', '5000000',
            '-probesize', '32768',
            '-analyzeduration', '100000',
            '-show_entries', 'stream=index,codec_type,codec_name,profile,level,width,height,channels,sample_rate',
            '-of', 'json',
            $this->rtspUrl($cameraId),
        ];
    }

    public function streamCommand(string $cameraId, string $codec, bool $audio = false): array
    {
        if (!in_array($codec, ['h264', 'hevc'], true)) {
            throw new InvalidArgumentException('This camera video codec is not supported.');
        }

        $transport = $this->transport($cameraId);
        $command = [
            $this->ffmpeg,
            '-nostdin',
            '-hide_banner',
            '-loglevel', 'error',
            '-rtsp_transport', $transport,
            '-timeout', '5000000',
            '-fflags', '+genpts',
        ];
        if ($transport === 'udp') {
            array_push(
                $command,
                '-max_delay', '100000',
                '-reorder_queue_size', '16'
            );
        }
        array_push(
            $command,
            '-i', $this->rtspUrl($cameraId),
            '-map', '0:v:0',
            '-c:v', 'copy',
        );
        if ($audio) {
            array_push(
                $command,
                '-map', '0:a:0?',
                '-c:a', 'aac',
                '-profile:a', 'aac_low',
                '-ac', '1',
                '-ar', '48000',
                '-b:a', '64k'
            );
        } else {
            $command[] = '-an';
        }
        if ($codec === 'hevc') {
            array_push($command, '-tag:v', 'hvc1');
        }
        array_push(
            $command,
            '-movflags', 'frag_keyframe+empty_moov+default_base_moof',
            '-frag_duration', '100000',
            '-flush_packets', '1',
            '-f', 'mp4',
            'pipe:1'
        );

        return $command;
    }

    public function relayName(string $cameraId): string
    {
        $this->camera($cameraId);
        if (preg_match('/^[a-z][a-z0-9_-]*$/D', $cameraId) !== 1) {
            throw new InvalidArgumentException('Camera identifier is invalid.');
        }

        return 'alina-' . $cameraId;
    }

    public function relaySource(string $cameraId): string
    {
        return $this->rtspUrl($cameraId) . '#transport=' . $this->transport($cameraId);
    }

    public function lockKey(string $cameraId): string
    {
        $camera = $this->camera($cameraId);
        return hash('sha256', $cameraId . "\0" . $camera['host']);
    }

    private function rtspUrl(string $cameraId): string
    {
        $camera = $this->camera($cameraId);
        $credentials = $camera['access']['credentials']['rtsp'] ?? $camera['dvrip'];
        $port = $camera['access']['ports']['rtsp'] ?? 554;

        return 'rtsp://'
            . rawurlencode($credentials['username'])
            . ':'
            . rawurlencode($credentials['password'])
            . '@'
            . $camera['host']
            . ':'
            . $port
            . '/';
    }

    private function transport(string $cameraId): string
    {
        return $this->camera($cameraId)['live']['rtsp_transport'] ?? 'tcp';
    }

    private function camera(string $cameraId): array
    {
        $camera = $this->config->camera($cameraId);
        if (filter_var($camera['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new RuntimeException('The configured camera address is invalid.');
        }

        return $camera;
    }
}
