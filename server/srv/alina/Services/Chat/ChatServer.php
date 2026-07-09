<?php

namespace alina\Services\Chat;

use alina\Utils\Data;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class ChatServer implements MessageComponentInterface
{
    private array $lastMessages = [];

    protected $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage(); // Храним подключённые клиенты
        echo "Сервер запущен!\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        /** @var \Ratchet\WebSocket\WsConnection|\Ratchet\Server\IoConnection $conn */
        echo "Новое подключение: {$conn->resourceId}\n";

        $conn->send(___('Connection established.'));
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $this->lastMessagesStorage($msg);

        foreach ($this->clients as $client) {
            if ($from === $client) {
                $doShowLastMessages = false;

                if (Data::isStringValidJson($msg, $objMessage)) {
                    $doShowLastMessages = (int) ($objMessage?->stateChatJustOpened ?? 0) === 1;
                }

                $payload = $doShowLastMessages ? json_encode($this->lastMessages) : $msg;
                $client->send($payload);

                continue;
            }

            $client->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        /** @var \Ratchet\WebSocket\WsConnection|\Ratchet\Server\IoConnection $conn */
        echo "Подключение {$conn->resourceId} закрыто\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "Ошибка: {$e->getMessage()}\n";
        $conn->close();
    }

    private function lastMessagesStorage(?string $msg = null)
    {
        $this->lastMessages[] = $msg;

        if (count($this->lastMessages) > 50) {
            array_shift($this->lastMessages);
        }

        return $this->lastMessages;
    }
}
