<?php

declare(strict_types=1);

namespace Cam;

use RuntimeException;

final class TalkAudioConverter
{
    private const FRAME_BYTES = 320;
    private const MAXIMUM_QUEUE_BYTES = 262_144;

    private mixed $process = null;
    /** @var array<int, resource> */
    private array $pipes = [];
    private string $pcmQueue = '';
    private string $alawQueue = '';

    public function __construct(
        private string $ffmpeg = '/usr/bin/ffmpeg',
        private string $logPath = '/var/log/php/cam-talk-ffmpeg.log',
    ) {
    }

    /** @return list<string> */
    public function command(): array
    {
        return [
            $this->ffmpeg,
            '-nostdin',
            '-hide_banner',
            '-loglevel', 'warning',
            '-f', 's16le',
            '-ar', '8000',
            '-ac', '1',
            '-i', 'pipe:0',
            '-f', 'alaw',
            '-ar', '8000',
            '-ac', '1',
            'pipe:1',
        ];
    }

    public function open(): void
    {
        if (is_resource($this->process)) throw new RuntimeException('Talk audio converter is already open.');
        if (!is_executable($this->ffmpeg) || !function_exists('proc_open')) {
            throw new RuntimeException('FFmpeg is unavailable for camera talkback.');
        }
        $this->process = proc_open($this->command(), [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', $this->logPath, 'a'],
        ], $this->pipes);
        if (!is_resource($this->process)) throw new RuntimeException('Could not start talk audio converter.');
        stream_set_blocking($this->pipes[0], false);
        stream_set_blocking($this->pipes[1], false);
    }

    public function push(string $pcm): void
    {
        if (!is_resource($this->process)) throw new RuntimeException('Talk audio converter is closed.');
        if ($pcm === '' || strlen($pcm) > 65_536 || strlen($pcm) % 2 !== 0) {
            throw new RuntimeException('Talk audio input is invalid.');
        }
        $this->pcmQueue .= $pcm;
        if (strlen($this->pcmQueue) > self::MAXIMUM_QUEUE_BYTES) {
            throw new RuntimeException('Talk audio input is arriving too quickly.');
        }
        $this->flushInput();
    }

    /** @return list<string> */
    public function drain(): array
    {
        if (!is_resource($this->process)) throw new RuntimeException('Talk audio converter is closed.');
        $this->flushInput();
        while (true) {
            $chunk = fread($this->pipes[1], 65_536);
            if ($chunk === false) throw new RuntimeException('Could not read converted talk audio.');
            if ($chunk === '') break;
            $this->alawQueue .= $chunk;
            if (strlen($this->alawQueue) > self::MAXIMUM_QUEUE_BYTES) {
                throw new RuntimeException('Converted talk audio queue is too large.');
            }
        }
        $status = proc_get_status($this->process);
        if (($status['running'] ?? false) !== true && feof($this->pipes[1])) {
            throw new RuntimeException('Talk audio converter stopped unexpectedly.');
        }
        return $this->consumeAlaw('');
    }

    /** @return list<string> */
    public function consumeAlaw(string $bytes): array
    {
        $this->alawQueue .= $bytes;
        if (strlen($this->alawQueue) > self::MAXIMUM_QUEUE_BYTES) {
            throw new RuntimeException('Converted talk audio queue is too large.');
        }
        $frames = [];
        while (strlen($this->alawQueue) >= self::FRAME_BYTES) {
            $frames[] = substr($this->alawQueue, 0, self::FRAME_BYTES);
            $this->alawQueue = substr($this->alawQueue, self::FRAME_BYTES);
        }
        return $frames;
    }

    public function close(): void
    {
        $this->pcmQueue = '';
        $this->alawQueue = '';
        if (isset($this->pipes[0]) && is_resource($this->pipes[0])) fclose($this->pipes[0]);
        if (is_resource($this->process)) proc_terminate($this->process);
        if (isset($this->pipes[1]) && is_resource($this->pipes[1])) fclose($this->pipes[1]);
        if (is_resource($this->process)) proc_close($this->process);
        $this->process = null;
        $this->pipes = [];
    }

    private function flushInput(): void
    {
        while ($this->pcmQueue !== '') {
            $written = fwrite($this->pipes[0], $this->pcmQueue);
            if ($written === false) throw new RuntimeException('Could not write talk audio to FFmpeg.');
            if ($written === 0) break;
            $this->pcmQueue = substr($this->pcmQueue, $written);
        }
    }
}
