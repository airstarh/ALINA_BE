<?php

declare(strict_types=1);

namespace Cam;

use Closure;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class LiveRecorder
{
    private Closure $openStream;
    private Closure $clock;

    public function __construct(
        private Config $config,
        private LiveStream $live,
        private Go2rtcClient $relay,
        private string $stateDirectory = '/tmp',
        private string $relayBaseUrl = 'http://go2rtc:1984/cam-stream',
        ?Closure $openStream = null,
        ?Closure $clock = null,
    ) {
        $this->openStream = $openStream ?? static function (string $url) {
            return @fopen($url, 'rb', false, stream_context_create([
                'http' => ['timeout' => 15, 'ignore_errors' => false, 'header' => "Connection: close\r\n"],
            ]));
        };
        $this->clock = $clock ?? static fn (): DateTimeImmutable => new DateTimeImmutable();
    }

    public function start(string $cameraId): LiveRecordingSession
    {
        $settings = $this->config->recording();
        if ($settings === null) {
            throw new RuntimeException('Camera recording is not configured.');
        }
        if (!is_dir($settings['storage']) || !is_writable($settings['storage'])) {
            throw new RuntimeException('Camera recording storage does not exist or is not writable.');
        }

        $stream = $this->live->relayName($cameraId);
        $lockPath = $this->path($cameraId, 'lock');
        $lock = @fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('This camera is already being recorded.');
        }

        $stopPath = $this->path($cameraId, 'stop');
        $statePath = $this->path($cameraId, 'json');
        @unlink($stopPath);
        @unlink($statePath);
        $output = null;
        $input = null;
        $temporary = null;
        try {
            $filename = $this->availableFilename($settings['storage'], $cameraId);
            $final = $settings['storage'] . '/' . $filename;
            $temporary = $final . '.part';
            $output = @fopen($temporary, 'x+b');
            if ($output === false) {
                throw new RuntimeException('Could not create the camera recording file.');
            }

            $this->relay->upsert($stream, $this->live->relaySource($cameraId));
            $query = ['src' => $stream, 'mp4' => 'flac', 'filename' => $filename];
            if ($settings['lengthMax'] > 0) {
                $query['duration'] = $settings['lengthMax'];
            }
            $url = rtrim($this->relayBaseUrl, '/') . '/api/stream.mp4?'
                . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            $input = ($this->openStream)($url);
            if (!is_resource($input)) {
                throw new RuntimeException('Camera relay could not start the recording stream.');
            }

            $metadata = [
                'active' => true,
                'stopping' => false,
                'camera' => $cameraId,
                'filename' => $filename,
                'startedAt' => ($this->clock)()->format(DATE_ATOM),
                'lengthMax' => $settings['lengthMax'],
            ];
            if (@file_put_contents($statePath, json_encode($metadata, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new RuntimeException('Could not create camera recording state.');
            }

            return new LiveRecordingSession(
                $input,
                $output,
                $lock,
                $temporary,
                $final,
                $stopPath,
                $statePath,
                $metadata,
            );
        } catch (Throwable $error) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
            if (is_string($temporary) && is_file($temporary)) @unlink($temporary);
            @unlink($stopPath);
            @unlink($statePath);
            flock($lock, LOCK_UN);
            fclose($lock);
            throw $error;
        }
    }

    /** @return array{active: bool, stopping: bool, camera?: string, filename?: string, startedAt?: string, lengthMax?: int} */
    public function status(string $cameraId): array
    {
        $this->config->camera($cameraId);
        $lock = @fopen($this->path($cameraId, 'lock'), 'c');
        if ($lock === false) {
            return ['active' => false, 'stopping' => false];
        }
        if (flock($lock, LOCK_EX | LOCK_NB)) {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($this->path($cameraId, 'json'));
            @unlink($this->path($cameraId, 'stop'));
            return ['active' => false, 'stopping' => false];
        }
        fclose($lock);

        $state = json_decode((string) @file_get_contents($this->path($cameraId, 'json')), true);
        if (!is_array($state)) {
            return ['active' => true, 'stopping' => is_file($this->path($cameraId, 'stop'))];
        }
        $state['active'] = true;
        $state['stopping'] = is_file($this->path($cameraId, 'stop'));
        return $state;
    }

    /** @return array{active: bool, stopping: bool} */
    public function stop(string $cameraId): array
    {
        $status = $this->status($cameraId);
        if (!$status['active']) {
            return ['active' => false, 'stopping' => false];
        }
        if (@file_put_contents($this->path($cameraId, 'stop'), 'stop', LOCK_EX) === false) {
            throw new RuntimeException('Could not stop the camera recording.');
        }
        return ['active' => true, 'stopping' => true];
    }

    private function availableFilename(string $storage, string $cameraId): string
    {
        $prefix = ($this->clock)()->format('Y-m-d---H-i') . '---' . $cameraId;
        $number = 1;
        do {
            $suffix = $number === 1 ? '' : '---' . $number;
            $filename = $prefix . $suffix . '.mp4';
            $number++;
        } while (is_file($storage . '/' . $filename) || is_file($storage . '/' . $filename . '.part'));

        return $filename;
    }

    private function path(string $cameraId, string $extension): string
    {
        return rtrim($this->stateDirectory, '/') . '/alina-cam-record-' . $cameraId . '.' . $extension;
    }
}

final class LiveRecordingSession
{
    private bool $closed = false;

    public function __construct(
        private mixed $input,
        private mixed $output,
        private mixed $lock,
        private string $temporary,
        private string $final,
        private string $stopPath,
        private string $statePath,
        private array $metadata,
    ) {
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function capture(): void
    {
        $written = 0;
        try {
            while (!is_file($this->stopPath) && !feof($this->input)) {
                $chunk = fread($this->input, 65536);
                if ($chunk === false) {
                    throw new RuntimeException('Could not read the camera recording stream.');
                }
                if ($chunk === '') {
                    $metadata = stream_get_meta_data($this->input);
                    if (($metadata['timed_out'] ?? false) === true) {
                        throw new RuntimeException('Camera recording stream timed out.');
                    }
                    usleep(20000);
                    continue;
                }
                $offset = 0;
                $length = strlen($chunk);
                while ($offset < $length) {
                    $count = fwrite($this->output, substr($chunk, $offset));
                    if ($count === false || $count === 0) {
                        throw new RuntimeException('Could not write the camera recording file.');
                    }
                    $offset += $count;
                    $written += $count;
                }
            }
            fflush($this->output);
            $this->closeHandles();
            if ($written > 0 && !@rename($this->temporary, $this->final)) {
                throw new RuntimeException('Could not finish the camera recording file.');
            }
            if ($written === 0) @unlink($this->temporary);
        } catch (Throwable $error) {
            $this->closeHandles();
            @unlink($this->temporary);
            throw $error;
        } finally {
            $this->release();
        }
    }

    public function __destruct()
    {
        if (!$this->closed) {
            $this->closeHandles();
            @unlink($this->temporary);
            $this->release();
        }
    }

    private function closeHandles(): void
    {
        if (is_resource($this->input)) fclose($this->input);
        if (is_resource($this->output)) fclose($this->output);
        $this->input = null;
        $this->output = null;
    }

    private function release(): void
    {
        if ($this->closed) return;
        $this->closed = true;
        @unlink($this->stopPath);
        @unlink($this->statePath);
        if (is_resource($this->lock)) {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
        }
        $this->lock = null;
    }
}
