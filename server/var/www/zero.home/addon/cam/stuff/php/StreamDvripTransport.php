<?php

declare(strict_types=1);

namespace Cam;

use Closure;

final class StreamDvripTransport implements DvripTransport
{
    /** @var resource|null */
    private $stream = null;

    public function __construct(
        private string $host,
        private int $port,
        private float $timeoutSeconds = 5.0,
        private ?Closure $connector = null,
    ) {
    }

    public function connect(): void
    {
        if (is_resource($this->stream)) {
            return;
        }

        if ($this->connector !== null) {
            $stream = ($this->connector)();
        } else {
            $errno = 0;
            $error = '';
            $stream = @stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno,
                $error,
                $this->timeoutSeconds,
                STREAM_CLIENT_CONNECT
            );
        }

        if (!is_resource($stream)) {
            throw new DvripException('transport_connect', 'Could not connect to camera.');
        }

        $seconds = (int) floor($this->timeoutSeconds);
        $microseconds = (int) (($this->timeoutSeconds - $seconds) * 1_000_000);
        stream_set_blocking($stream, true);
        stream_set_timeout($stream, $seconds, $microseconds);
        $this->stream = $stream;
    }

    public function writeAll(string $bytes): void
    {
        $stream = $this->requireStream();
        $written = 0;
        $length = strlen($bytes);
        $deadline = $this->now() + $this->timeoutSeconds;

        while ($written < $length) {
            $this->applyRemainingTimeout($stream, $deadline);
            $count = @fwrite($stream, substr($bytes, $written));
            if ($count === false || $count === 0) {
                $this->throwStreamFailure('write');
            }
            $written += $count;
        }
    }

    public function readExact(int $length): string
    {
        if ($length < 0) {
            throw new DvripException('transport_length', 'Invalid read length.');
        }

        $stream = $this->requireStream();
        $buffer = '';
        $deadline = $this->now() + $this->timeoutSeconds;
        while (strlen($buffer) < $length) {
            $this->applyRemainingTimeout($stream, $deadline);
            $chunk = @fread($stream, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                $this->throwStreamFailure('read');
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    /** @return resource */
    private function requireStream()
    {
        if (!is_resource($this->stream)) {
            throw new DvripException('transport_closed', 'Camera connection is not open.');
        }

        return $this->stream;
    }

    private function throwStreamFailure(string $operation): never
    {
        $stream = $this->requireStream();
        $metadata = stream_get_meta_data($stream);
        if (($metadata['timed_out'] ?? false) === true) {
            throw new DvripException('transport_timeout', "Camera {$operation} timed out.");
        }
        if (feof($stream)) {
            throw new DvripException('transport_eof', "Camera closed during {$operation}.");
        }

        throw new DvripException('transport_io', "Camera {$operation} failed.");
    }

    /** @param resource $stream */
    private function applyRemainingTimeout($stream, float $deadline): void
    {
        $remaining = $deadline - $this->now();
        if ($remaining <= 0) {
            throw new DvripException('transport_timeout', 'Camera operation timed out.');
        }
        $seconds = (int) floor($remaining);
        $microseconds = max(1, (int) (($remaining - $seconds) * 1_000_000));
        stream_set_timeout($stream, $seconds, $microseconds);
    }

    private function now(): float
    {
        return hrtime(true) / 1_000_000_000;
    }
}
