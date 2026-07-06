<?php

namespace alina\Services\Chat;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class ChatServer implements MessageComponentInterface
{
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

        $conn->send(___('"Connection established."'));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Отправляем сообщение всем, кроме отправителя
        foreach ($this->clients as $client) {
            $client->send($msg);
            // if ($from !== $client) {
            //     $client->send($msg);
            // }
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
}
