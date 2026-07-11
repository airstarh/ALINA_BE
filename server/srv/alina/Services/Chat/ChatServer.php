<?php

namespace alina\Services\Chat;

use alina\Utils\Data;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;
use Throwable;

class ChatServer implements MessageComponentInterface
{
    // Global client registry (optional, mainly for logging/cleanup)
    private SplObjectStorage $clients;

    // channelId => SplObjectStorage of clients in this channel
    private array $channels = [];

    // Per-channel last messages
    private array $lastMessages = [];

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
        echo "Сервер запущен!\n";
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        echo "Новое подключение: {$conn->resourceId}\n";

        // Optionally send an initial handshake; real channel assignment happens after receiving first message
        $conn->send(___('Connection established.'));
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // Expect JSON with at least: {"channel": "...", "text": "..."}
        if (! Data::isStringValidJson($msg, $obj)) {
            // If not JSON, ignore or handle as error
            return;
        }

        $channel = $obj->channel ?? '';

        if ($channel === '') {
            // Reject messages without channel
            $from->send(json_encode(['error' => 'Missing channel']));

            return;
        }

        // Ensure channel exists
        if (! isset($this->channels[$channel])) {
            $this->channels[$channel]     = new SplObjectStorage();
            $this->lastMessages[$channel] = [];
        }

        // Join/re-join channel on message (simple strategy)
        if (! $this->channels[$channel]->contains($from)) {
            $this->channels[$channel]->attach($from);
        }

        // Store message in channel history
        $this->storeLastMessage($channel, $msg);

        // Determine if this client wants initial history (e.g. stateChatJustOpened)
        $doShowLastMessages = (int)($obj->stateChatJustOpened ?? 0) === 1;

        /** @var SplObjectStorage $roomClients */
        $roomClients = $this->channels[$channel];

        foreach ($roomClients as $client) {
            try {
                if ($doShowLastMessages) {
                    // Send full history to this client
                    $client->send(json_encode($this->lastMessages[$channel]));
                }
                else {
                    // Forward the message itself
                    $client->send($msg);
                }
            }
            catch (Throwable $e) {
                $client->send($e->getMessage());
            }
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);

        // Remove from all channels
        foreach ($this->channels as $channel => $clientsInChannel) {
            if ($clientsInChannel->contains($conn)) {
                $clientsInChannel->detach($conn);

                if ($clientsInChannel->count() === 0) {
                    unset($this->channels[$channel]);
                    unset($this->lastMessages[$channel]);
                }
            }
        }

        echo "Подключение {$conn->resourceId} закрыто\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        echo "Ошибка: {$e->getMessage()}\n";
        $conn->close();
    }

    private function storeLastMessage(string $channel, ?string $msg): array
    {
        if (! isset($this->lastMessages[$channel])) {
            $this->lastMessages[$channel] = [];
        }

        $this->lastMessages[$channel][] = $msg;

        if (count($this->lastMessages[$channel]) > 50) {
            array_shift($this->lastMessages[$channel]);
        }

        return $this->lastMessages[$channel];
    }
}
