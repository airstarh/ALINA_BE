<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use Closure;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;
use Throwable;

final class FileJobServer implements MessageComponentInterface
{
    private const MAX_MESSAGE_BYTES = 8192;
    private SplObjectStorage $clients;
    private Closure $now;

    public function __construct(private readonly FileJobStorage $storage, ?callable $now = null)
    {
        $this->clients = new SplObjectStorage();
        $this->now = Closure::fromCallable($now ?? static fn (): int => time());
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients[$conn] = ['publicId' => null, 'userId' => null];
        $this->send($conn, ['type' => 'connected']);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (! $this->clients->contains($from) || ! is_string($msg) || strlen($msg) > self::MAX_MESSAGE_BYTES) {
            $this->error($from, 'Invalid file job payload.');
            return;
        }

        try {
            $data = json_decode($msg, true, 16, JSON_THROW_ON_ERROR);
        }
        catch (Throwable) {
            $this->error($from, 'Invalid file job payload.');
            return;
        }

        if (! is_array($data) || array_is_list($data)) {
            $this->error($from, 'Invalid file job payload.');
            return;
        }

        $type = $data['type'] ?? null;
        $publicId = $data['publicId'] ?? null;
        if (! is_string($publicId) || ! preg_match('/^[A-Za-z0-9-]{1,64}$/D', $publicId)) {
            $this->error($from, 'Invalid file job identifier.');
            return;
        }

        if ($type === 'subscribe') {
            $this->subscribe($from, $data, $publicId);
            return;
        }
        if ($type === 'status') {
            $this->status($from, $publicId);
            return;
        }

        $this->error($from, 'Unknown file job event.');
    }

    public function onClose(ConnectionInterface $conn): void
    {
        if ($this->clients->contains($conn)) {
            $this->clients->detach($conn);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        error_log('File job socket error: ' . $e->getMessage());
        $this->onClose($conn);
        $conn->close();
    }

    public function publishJob(string $publicId): void
    {
        $snapshot = $this->storage->snapshot($publicId);
        if ($snapshot === null) {
            return;
        }

        foreach ($this->clients as $client) {
            $state = $this->clients[$client];
            if (($state['publicId'] ?? null) === $publicId) {
                $this->send($client, ['type' => 'job', 'job' => $snapshot]);
            }
        }
    }

    private function subscribe(ConnectionInterface $client, array $data, string $publicId): void
    {
        $userId = filter_var($data['uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $token = $data['token'] ?? null;
        if ($userId === false || ! is_string($token) || strlen($token) < 10 || strlen($token) > 512) {
            $this->error($client, 'File job not found or access denied.');
            return;
        }

        $snapshot = $this->storage->authorizedSnapshot($publicId, (int) $userId, $token, ($this->now)());
        if ($snapshot === null) {
            $this->error($client, 'File job not found or access denied.');
            return;
        }

        $this->clients[$client] = ['publicId' => $publicId, 'userId' => (int) $userId];
        $this->send($client, ['type' => 'subscribed', 'job' => $snapshot]);
    }

    private function status(ConnectionInterface $client, string $publicId): void
    {
        $state = $this->clients[$client];
        if (($state['publicId'] ?? null) !== $publicId) {
            $this->error($client, 'Subscribe to the file job first.');
            return;
        }

        $snapshot = $this->storage->snapshot($publicId);
        if ($snapshot === null) {
            $this->error($client, 'File job not found or access denied.');
            return;
        }
        $this->send($client, ['type' => 'job', 'job' => $snapshot]);
    }

    private function error(ConnectionInterface $client, string $message): void
    {
        $this->send($client, ['type' => 'error', 'message' => $message]);
    }

    private function send(ConnectionInterface $client, array $payload): void
    {
        $client->send(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
