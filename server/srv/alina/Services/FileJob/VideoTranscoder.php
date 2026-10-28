<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use InvalidArgumentException;
use RuntimeException;

final class VideoTranscoder
{
    public function __construct(
        private readonly string $ffmpeg = '/usr/bin/ffmpeg',
        private readonly string $ffprobe = '/usr/bin/ffprobe',
    ) {
    }

    public function probe(string $source): array
    {
        $result = $this->run([
            $this->ffprobe,
            '-v', 'error',
            '-show_streams',
            '-show_format',
            '-of', 'json',
            $source,
        ]);

        if (! $result->succeeded()) {
            throw new InvalidArgumentException('The uploaded file is not a readable video.');
        }

        return $this->parseProbe($result->stdout);
    }

    public function parseProbe(string $json): array
    {
        $data = json_decode($json, true);
        if (! is_array($data)) {
            throw new InvalidArgumentException('Invalid ffprobe response.');
        }

        $streams = is_array($data['streams'] ?? null) ? $data['streams'] : [];
        $video = null;
        $hasAudio = false;
        foreach ($streams as $stream) {
            if (! is_array($stream)) continue;
            if (($stream['codec_type'] ?? null) === 'video' && $video === null) $video = $stream;
            if (($stream['codec_type'] ?? null) === 'audio') $hasAudio = true;
        }
        if ($video === null) {
            throw new InvalidArgumentException('The uploaded file contains no video stream.');
        }

        $duration = (float) ($data['format']['duration'] ?? $video['duration'] ?? 0);
        if (! is_finite($duration) || $duration <= 0) {
            throw new InvalidArgumentException('The video duration is unavailable.');
        }

        return ['duration' => $duration, 'hasAudio' => $hasAudio];
    }

    public function command(string $source, string $output, bool $hasAudio): array
    {
        $command = [
            $this->ffmpeg,
            '-y', '-nostdin', '-v', 'error',
            '-i', $source,
            '-map', '0:v:0',
        ];
        if ($hasAudio) {
            $command = [...$command, '-map', '0:a?'];
        }

        return [
            ...$command,
            '-vf', 'scale=min(1280\\,iw):-2',
            '-r', '30',
            '-c:v', 'libx264',
            '-crf', '23',
            '-preset', 'veryfast',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', '64k',
            '-movflags', '+faststart',
            '-progress', 'pipe:1',
            '-nostats',
            '-f', 'mp4',
            $output,
        ];
    }

    public function progress(string $output, float $duration, int $previous): int
    {
        if (preg_match('/(?:^|\R)progress=end(?:\R|$)/', $output)) {
            return 100;
        }

        $latest = null;
        if (preg_match_all('/(?:^|\R)(?:out_time_us|out_time_ms)=([0-9]+)(?:\R|$)/', $output, $matches)) {
            $latest = (int) end($matches[1]);
        }
        if ($latest === null || $duration <= 0) {
            return max(0, min(99, $previous));
        }

        $calculated = (int) floor(($latest / 1_000_000) / $duration * 100);

        return max(0, min(99, max($previous, $calculated)));
    }

    /** @return array{resource: resource, stdout: resource, stderr: resource} */
    public function start(string $source, string $output, bool $hasAudio): array
    {
        $pipes = [];
        $process = proc_open(
            $this->command($source, $output, $hasAudio),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true],
        );
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start video conversion.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return ['resource' => $process, 'stdout' => $pipes[1], 'stderr' => $pipes[2]];
    }

    private function run(array $command): ProcessResult
    {
        $pipes = [];
        $process = proc_open($command, [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
        if (! is_resource($process)) throw new RuntimeException('Unable to start media inspection.');
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return new ProcessResult($exitCode, $stdout === false ? '' : $stdout, $stderr === false ? '' : $stderr);
    }
}
