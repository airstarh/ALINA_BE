<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Chat/ChatReply.php';

use alina\Services\Chat\ChatStorage;
use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;

final class MemoryChatStorage implements ChatStorage
{
    private int $nextChatId = 1;
    private int $nextMessageId = 1;
    private array $chats = [];
    private array $messages = [];
    public ?Throwable $nextError = null;

    public function openChat(string $origin, string $channel): array
    {
        $this->maybeFail();
        $key = $this->chatKey($origin, $channel);

        if (! isset($this->chats[$key])) {
            $now = $this->now();
            $this->chats[$key] = [
                'id' => $this->nextChatId++,
                'origin' => $origin,
                'channel' => $channel,
                'name' => $channel,
                'isAnionAllowed' => true,
                'isConfirmationRequired' => false,
                'createdAt' => $now,
                'updatedAt' => $now,
            ];
        }

        return $this->chats[$key];
    }

    public function listRooms(string $origin, string $senderType, string $senderId, array $pagination): array
    {
        $this->maybeFail();
        $mine = [];
        $others = [];

        foreach ($this->chats as $chat) {
            if ($chat['origin'] !== $origin) {
                continue;
            }

            $participants = [];
            $latestMessageAt = null;
            $participated = false;

            foreach ($this->messages[$chat['id']] ?? [] as $message) {
                $latestMessageAt = max($latestMessageAt ?? $message['createdAt'], $message['createdAt']);

                if ($message['senderType'] === 'bot') {
                    continue;
                }

                $participants[$message['senderType'] . "\0" . $message['senderId']] = true;
                $participated = $participated || (
                    $message['senderType'] === $senderType
                    && $message['senderId'] === $senderId
                );
            }

            $row = [
                'id' => $chat['id'],
                'channel' => $chat['channel'],
                'chatName' => $chat['name'],
                'participantCount' => count($participants),
                'latestMessageAt' => $latestMessageAt,
            ];
            if ($participated) {
                $mine[] = $row;
            }
            else {
                $others[] = $row;
            }
        }

        $sort = static function (array $left, array $right): int {
            if ($left['latestMessageAt'] === null || $right['latestMessageAt'] === null) {
                return $left['latestMessageAt'] === $right['latestMessageAt']
                    ? $right['id'] <=> $left['id']
                    : ($left['latestMessageAt'] === null ? 1 : -1);
            }

            return ($right['latestMessageAt'] <=> $left['latestMessageAt'])
                ?: ($right['id'] <=> $left['id']);
        };
        usort($mine, $sort);
        usort($others, $sort);

        $section = static function (array $rows, array $page): array {
            return [
                'rows' => array_slice($rows, $page['offset'], $page['limit']),
                'total' => count($rows),
                'limit' => $page['limit'],
                'offset' => $page['offset'],
            ];
        };

        return [
            'mine' => $section($mine, $pagination['mine']),
            'others' => $section($others, $pagination['others']),
        ];
    }

    public function configureChat(string $origin, string $channel, array $changes): array
    {
        $this->openChat($origin, $channel);
        $key = $this->chatKey($origin, $channel);
        $this->chats[$key] = array_replace($this->chats[$key], $changes);

        return $this->chats[$key];
    }

    public function history(int $chatId, int $limit = 50): array
    {
        $this->maybeFail();

        return array_slice($this->messages[$chatId] ?? [], -$limit);
    }

    public function hasAnyMessage(int $chatId): bool
    {
        $this->maybeFail();

        return ! empty($this->messages[$chatId]);
    }

    public function hasParticipantMessage(int $chatId, string $senderType, string $senderId): bool
    {
        $this->maybeFail();

        if ($senderType === 'bot') {
            return false;
        }

        foreach ($this->messages[$chatId] ?? [] as $message) {
            if (
                $message['senderType'] !== 'bot'
                && $message['senderType'] === $senderType
                && $message['senderId'] === $senderId
            ) {
                return true;
            }
        }

        return false;
    }

    public function appendMessage(int $chatId, array $message): array
    {
        $this->maybeFail();

        foreach ($this->messages[$chatId] ?? [] as $stored) {
            if ($stored['clientMessageId'] === $message['clientMessageId']) {
                return ['message' => $stored, 'inserted' => false];
            }
        }

        $stored = ['id' => $this->nextMessageId++] + $message;
        $this->messages[$chatId][] = $stored;
        $this->messages[$chatId] = array_slice($this->messages[$chatId], -1000);

        return ['message' => $stored, 'inserted' => true];
    }

    public function appendReply(int $chatId, int $parentMessageId, array $message): array
    {
        $this->maybeFail();

        foreach ($this->messages[$chatId] ?? [] as $stored) {
            if ($stored['clientMessageId'] === $message['clientMessageId']) {
                return ['message' => $stored, 'inserted' => false];
            }
        }

        foreach ($this->messages[$chatId] ?? [] as $parent) {
            if ($parent['id'] === $parentMessageId) {
                $message['text'] = \alina\Services\Chat\ChatReply::compose($parent, $message['text']);

                if (strlen($message['text']) > 16000) {
                    throw new RuntimeException('Message is too large.');
                }

                return $this->appendMessage($chatId, $message);
            }
        }

        throw new RuntimeException('Message not found.');
    }

    public function editMessage(int $chatId, int $messageId, string $text): array
    {
        $this->maybeFail();

        foreach ($this->messages[$chatId] ?? [] as $index => $message) {
            if ($message['id'] === $messageId) {
                $text = \alina\Services\Chat\ChatReply::replaceOwnText($message['text'], $text);

                if (strlen($text) > 16000) {
                    throw new RuntimeException('Message is too large.');
                }

                $this->messages[$chatId][$index]['text'] = $text;

                return $this->messages[$chatId][$index];
            }
        }

        throw new RuntimeException('Message not found.');
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        $this->maybeFail();

        foreach ($this->messages[$chatId] ?? [] as $index => $message) {
            if ($message['id'] === $messageId) {
                array_splice($this->messages[$chatId], $index, 1);

                return true;
            }
        }

        return false;
    }

    public function renameChat(int $chatId, string $name, int $updatedAt): array
    {
        $this->maybeFail();

        foreach ($this->chats as &$chat) {
            if ($chat['id'] === $chatId) {
                $chat['name'] = $name;
                $chat['updatedAt'] = $updatedAt;

                return $chat;
            }
        }

        throw new RuntimeException('Unknown chat.');
    }

    public function setAnonymousAllowed(int $chatId, bool $allowed, int $updatedAt): array
    {
        $this->maybeFail();

        foreach ($this->chats as &$chat) {
            if ($chat['id'] === $chatId) {
                $chat['isAnionAllowed'] = $allowed;
                $chat['updatedAt'] = $updatedAt;

                return $chat;
            }
        }

        throw new RuntimeException('Unknown chat.');
    }

    public function setConfirmationRequired(int $chatId, bool $required, int $updatedAt): array
    {
        $this->maybeFail();

        foreach ($this->chats as &$chat) {
            if ($chat['id'] === $chatId) {
                $chat['isConfirmationRequired'] = $required;
                $chat['updatedAt'] = $updatedAt;

                return $chat;
            }
        }

        throw new RuntimeException('Unknown chat.');
    }

    public function deleteParticipantMessages(int $chatId, string $senderType, string $senderId): int
    {
        $this->maybeFail();
        $before = count($this->messages[$chatId] ?? []);
        $this->messages[$chatId] = array_values(array_filter(
            $this->messages[$chatId] ?? [],
            static fn (array $message): bool => ! (
                $message['senderType'] === $senderType
                && $message['senderId'] === $senderId
            ),
        ));

        return $before - count($this->messages[$chatId]);
    }

    public function chatCount(): int
    {
        return count($this->chats);
    }

    private function maybeFail(): void
    {
        if ($this->nextError === null) {
            return;
        }

        $error = $this->nextError;
        $this->nextError = null;

        throw $error;
    }

    private function chatKey(string $origin, string $channel): string
    {
        return $origin . "\0" . $channel;
    }

    private function now(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}

final class ChatTestClient implements ConnectionInterface
{
    public array $received = [];
    public bool $closed = false;
    public ?Request $httpRequest;

    public function __construct(public int $resourceId, ?string $origin = 'https://zero.home:50443')
    {
        $headers = $origin === null ? [] : ['Origin' => $origin];
        $this->httpRequest = new Request('GET', 'http://chat.local/', $headers);
    }

    public function send($data)
    {
        $this->received[] = $data;

        return $this;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function events(?string $type = null): array
    {
        $events = [];

        foreach ($this->received as $raw) {
            $event = json_decode((string) $raw, true);

            if (! is_array($event)) {
                continue;
            }

            if ($type === null || ($event['type'] ?? null) === $type) {
                $events[] = $event;
            }
        }

        return $events;
    }

    public function lastEvent(string $type): ?array
    {
        $events = $this->events($type);

        return $events === [] ? null : $events[array_key_last($events)];
    }
}

function chatExpectSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "FAIL: %s\nExpected: %s\nActual: %s",
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function chatExpect(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

function chatSend(object $server, ChatTestClient $client, string $type, array $extra = []): void
{
    static $messageSequence = 0;
    $payload = array_replace([
        'version' => 2,
        'type' => $type,
        'channel' => 'general',
        'CurrentUser' => ['id' => 17, 'nickname' => 'Alice'],
    ], $extra);

    if ($type === 'message' && ! array_key_exists('clientMessageId', $payload)) {
        $payload['clientMessageId'] = 'test-' . ++$messageSequence;
    }

    $server->onMessage($client, json_encode($payload, JSON_THROW_ON_ERROR));
}
