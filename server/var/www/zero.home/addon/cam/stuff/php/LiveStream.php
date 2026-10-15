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
        return [
            $this->ffprobe,
            '-v', 'quiet',
            '-rtsp_transport', 'tcp',
            '-rw_timeout', '5000000',
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

        $command = [
            $this->ffmpeg,
            '-nostdin',
            '-hide_banner',
            '-loglevel', 'error',
            '-rtsp_transport', 'tcp',
            '-timeout', '5000000',
            '-fflags', '+genpts',
            '-i', $this->rtspUrl($cameraId),
            '-map', '0:v:0',
            '-c:v', 'copy',
        ];
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

    private function camera(string $cameraId): array
    {
        $camera = $this->config->camera($cameraId);
        if (filter_var($camera['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new RuntimeException('The configured camera address is invalid.');
        }

        return $camera;
    }
}
