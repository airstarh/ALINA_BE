<?php

declare(strict_types=1);

namespace alina\Services\Chat;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use SplObjectStorage;
use Throwable;

class ChatServer implements MessageComponentInterface
{
    private SplObjectStorage $clients;
    private array $channels = [];
    private array $lastMessages = [];
    private int $messageSequence = 0;
    private string $instanceId;

    public function __construct(?LoopInterface $loop = null)
    {
        $this->clients = new SplObjectStorage();
        $this->instanceId = bin2hex(random_bytes(8));
        $loop?->addPeriodicTimer(2, fn () => $this->expireTyping());
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn, [
            'channel' => null,
            'version' => 1,
            'user' => [],
            'participantId' => 'guest:' . $conn->resourceId,
            'typingUntil' => 0,
        ]);
        // The legacy client expects a plain-text handshake.
        $this->send($conn, 'Connection established.');
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (!$this->clients->contains($from)) {
            return;
        }
        if (!is_string($msg) || strlen($msg) > 65536) {
            $this->error($from, 'Message is too large.');
            return;
        }
        try {
            $data = json_decode($msg, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->error($from, 'Invalid chat payload.');
            return;
        }
        if (!is_array($data) || array_is_list($data)) {
            $this->error($from, 'Invalid chat payload.');
            return;
        }

        $version = ($data['version'] ?? null) === 2 ? 2 : 1;
        $channel = $data['channel'] ?? '';
        if (!is_string($channel) || trim($channel) === '' || strlen($channel) > 128) {
            $this->error($from, 'A channel of 1–128 bytes is required.');
            return;
        }
        $channel = trim($channel);
        $text = $data['msg'] ?? '';
        $type = $version === 2 ? ($data['type'] ?? '') : (is_string($text) && str_starts_with($text, 'JOIN:') ? 'join' : 'message');
        if (!in_array($type, ['join', 'message', 'typing'], true)) {
            $this->error($from, 'Unknown chat event.');
            return;
        }
        if ($type === 'message' && (!is_string($text) || trim($text) === '' || strlen($text) > 16000)) {
            $this->error($from, 'Enter a message of at most 16000 bytes.');
            return;
        }

        $state = $this->clients[$from];
        if ($type === 'typing') {
            // Typing cannot implicitly join or change identity/channel.
            if ($state['channel'] !== $channel) {
                return;
            }
            $state['typingUntil'] = ($data['typing'] ?? false) === true ? microtime(true) + 6 : 0;
            $this->clients[$from] = $state;
            $this->broadcastPresence($channel);
            return;
        }

        $joined = $state['channel'] !== $channel;
        if ($joined && $state['channel'] !== null) {
            $this->leave($from);
        }
        $this->channels[$channel] ??= new SplObjectStorage();
        $this->lastMessages[$channel] ??= [];
        $this->channels[$channel]->attach($from);
        $state['channel'] = $channel;
        $state['version'] = $version;
        $state['user'] = $this->publicUser($data['CurrentUser'] ?? null, $from->resourceId);
        $state['participantId'] = $state['user']['id'] !== null ? 'user:' . $state['user']['id'] : 'guest:' . $from->resourceId;
        $state['typingUntil'] = 0;
        $this->clients[$from] = $state;

        if ($type === 'join') {
            $this->sendHistory($from, $channel);
            $this->broadcastPresence($channel);
            return;
        }
        if ($joined || ($data['stateChatJustOpened'] ?? 0) == 1) {
            $this->sendHistory($from, $channel);
        }
        $message = [
            'id' => $this->instanceId . ':' . ++$this->messageSequence,
            'channel' => $channel,
            'msg' => trim($text),
            'CurrentUser' => $state['user'],
            'participantId' => $state['participantId'],
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
        $this->lastMessages[$channel][] = $message;
        if (count($this->lastMessages[$channel]) > 50) {
            array_shift($this->lastMessages[$channel]);
        }
        $this->broadcast($channel, fn ($client) => $this->clients[$client]['version'] === 2
            ? ['version' => 2, 'type' => 'message', 'channel' => $channel, 'message' => $message]
            : $message);
        $this->broadcastPresence($channel);
    }

    private function publicUser($user, $connectionId): array
    {
        $user = is_array($user) ? $user : [];
        $id = $user['id'] ?? null;
        $id = (is_int($id) || is_string($id)) && ctype_digit((string) $id) && (int) $id > 0 ? (string) $id : null;
        $fields = ['id' => $id];
        foreach (['firstname', 'lastname', 'nickname', 'emblem'] as $field) {
            $fields[$field] = is_string($user[$field] ?? null) ? substr($user[$field], 0, $field === 'emblem' ? 2048 : 160) : '';
        }
        $fields['name'] = trim($fields['nickname']) ?: (trim($fields['firstname'] . ' ' . $fields['lastname']) ?: 'Guest ' . $connectionId);
        return $fields;
    }

    private function sendHistory(ConnectionInterface $client, string $channel): void
    {
        $messages = $this->lastMessages[$channel];
        $this->send($client, $this->clients[$client]['version'] === 2
            ? ['version' => 2, 'type' => 'history', 'channel' => $channel, 'messages' => $messages]
            : array_map(fn ($message) => json_encode($message, JSON_INVALID_UTF8_SUBSTITUTE), $messages));
    }

    private function broadcastPresence(string $channel): void
    {
        if (!isset($this->channels[$channel])) {
            return;
        }
        $people = [];
        foreach ($this->channels[$channel] as $client) {
            $state = $this->clients[$client];
            $id = $state['participantId'];
            $typing = $state['typingUntil'] > microtime(true);
            if (!isset($people[$id])) {
                $people[$id] = ['id' => $id, 'user' => $state['user'], 'status' => 'online', 'typing' => false];
            }
            $people[$id]['typing'] = $people[$id]['typing'] || $typing;
        }
        $this->broadcast($channel, fn ($client) => $this->clients[$client]['version'] === 2
            ? ['version' => 2, 'type' => 'presence', 'channel' => $channel, 'selfId' => $this->clients[$client]['participantId'], 'participants' => array_values($people)]
            : null);
    }

    private function expireTyping(): void
    {
        $changed = [];
        foreach ($this->clients as $client) {
            $state = $this->clients[$client];
            if ($state['typingUntil'] > 0 && $state['typingUntil'] <= microtime(true)) {
                $state['typingUntil'] = 0;
                $this->clients[$client] = $state;
                if ($state['channel'] !== null) {
                    $changed[$state['channel']] = true;
                }
            }
        }
        foreach (array_keys($changed) as $channel) {
            $this->broadcastPresence((string) $channel);
        }
    }

    private function leave(ConnectionInterface $client): void
    {
        $state = $this->clients[$client];
        $channel = $state['channel'];
        $state['channel'] = null;
        $state['typingUntil'] = 0;
        $this->clients[$client] = $state;
        if ($channel === null || !isset($this->channels[$channel])) {
            return;
        }
        $this->channels[$channel]->detach($client);
        if ($this->channels[$channel]->count() === 0) {
            // Preserve the existing active-room, in-memory history lifecycle.
            unset($this->channels[$channel], $this->lastMessages[$channel]);
        } else {
            $this->broadcastPresence($channel);
        }
    }

    private function broadcast(string $channel, callable $payload): void
    {
        if (!isset($this->channels[$channel])) {
            return;
        }
        // Iterate a snapshot: a failed send can detach a connection.
        foreach (iterator_to_array($this->channels[$channel]) as $client) {
            if ($this->clients->contains($client)) {
                $data = $payload($client);
                if ($data !== null) {
                    $this->send($client, $data);
                }
            }
        }
    }

    private function send(ConnectionInterface $client, $data): void
    {
        try {
            $client->send(is_string($data) ? $data : json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
        } catch (Throwable $e) {
            error_log('Chat send failed: ' . $e->getMessage());
            $this->onClose($client);
            try {
                $client->close();
            } catch (Throwable $ignored) {
            }
        }
    }

    private function error(ConnectionInterface $client, string $message): void
    {
        $this->send($client, ['version' => 2, 'type' => 'error', 'error' => $message]);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        if ($this->clients->contains($conn)) {
            $this->leave($conn);
            $this->clients->detach($conn);
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        error_log('Chat connection failed: ' . $e->getMessage());
        $this->onClose($conn);
        $conn->close();
    }
}
