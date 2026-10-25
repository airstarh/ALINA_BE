<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatStorage.php';
require __DIR__ . '/../Services/Chat/ChatReply.php';

use alina\Services\Chat\ChatStorage;

final class ContractMemoryChatStorage implements ChatStorage
{
    private int $nextChatId = 1;
    private int $nextMessageId = 1;
    private array $chats = [];
    private array $messages = [];

    public function openChat(string $origin, string $channel, ?string $initialName = null, bool $private = false): array
    {
        $key = $origin . "\0" . $channel;

        if (! isset($this->chats[$key])) {
            $now = (int) floor(microtime(true) * 1000);
            $this->chats[$key] = [
                'id' => $this->nextChatId++,
                'origin' => $origin,
                'channel' => $channel,
                'name' => $initialName ?? $channel,
                'isAnionAllowed' => ! $private,
                'isConfirmationRequired' => $private,
                'createdAt' => $now,
                'updatedAt' => $now,
            ];
        }

        return $this->chats[$key];
    }

    public function listRooms(string $origin, string $senderType, string $senderId, array $pagination): array
    {
        $sections = ['mine' => [], 'others' => []];

        foreach ($this->chats as $chat) {
            if ($chat['origin'] !== $origin) {
                continue;
            }

            $participants = [];
            $latest = null;
            $participated = false;

            foreach ($this->messages[$chat['id']] ?? [] as $message) {
                $latest = max($latest ?? $message['createdAt'], $message['createdAt']);

                if ($message['senderType'] === 'bot') {
                    continue;
                }

                $participants[$message['senderType'] . "\0" . $message['senderId']] = true;
                $participated = $participated || ($message['senderType'] === $senderType && $message['senderId'] === $senderId);
            }

            $sections[$participated ? 'mine' : 'others'][] = [
                'id' => $chat['id'],
                'channel' => $chat['channel'],
                'chatName' => $chat['name'],
                'participantCount' => count($participants),
                'latestMessageAt' => $latest,
            ];
        }

        $result = [];

        foreach ($sections as $name => $rows) {
            usort($rows, static fn (array $a, array $b): int =>
                ($a['latestMessageAt'] === null) <=> ($b['latestMessageAt'] === null)
                ?: (($b['latestMessageAt'] ?? 0) <=> ($a['latestMessageAt'] ?? 0))
                ?: ($b['id'] <=> $a['id'])
            );
            $page = $pagination[$name];
            $result[$name] = [
                'rows' => array_slice($rows, $page['offset'], $page['limit']),
                'total' => count($rows),
                'limit' => $page['limit'],
                'offset' => $page['offset'],
            ];
        }

        return $result;
    }

    public function mentionCandidates(string $origin, string $senderType, string $senderId, string $query, int $limit): array
    {
        return [];
    }

    public function participantSnapshots(int $chatId): array
    {
        $result = [];
        foreach (array_reverse($this->messages[$chatId] ?? []) as $message) {
            if (! in_array($message['senderType'], ['user', 'guest', 'user_service'], true)) continue;
            $type = $message['senderType'] === 'user_service' ? (ctype_digit($message['senderId']) ? 'user' : 'guest') : $message['senderType'];
            $result[$type . ':' . $message['senderId']] ??= [
                'senderType' => $type, 'senderId' => $message['senderId'],
                'senderName' => $message['senderName'], 'senderEmblem' => $message['senderEmblem'] ?? '',
            ];
        }
        return array_values($result);
    }

    public function history(int $chatId, int $limit = 200, ?int $beforeMessageId = null): array
    {
        $messages = array_values(array_filter(
            $this->messages[$chatId] ?? [],
            static fn (array $message): bool => $beforeMessageId === null || $message['id'] < $beforeMessageId,
        ));
        return array_slice($messages, -$limit);
    }

    public function hasAnyMessage(int $chatId): bool
    {
        return ! empty($this->messages[$chatId]);
    }

    public function hasParticipantMessage(int $chatId, string $senderType, string $senderId): bool
    {
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

    public function appendMessageGroup(int $chatId, array $message, array $targets): array
    {
        $messages = [];
        foreach ([...$targets, $message] as $item) $messages[] = $this->appendMessage($chatId, $item)['message'];
        return ['messages' => $messages, 'inserted' => true];
    }

    public function appendReply(int $chatId, int $parentMessageId, array $message): array
    {
        foreach ($this->messages[$chatId] ?? [] as $stored) {
            if ($stored['clientMessageId'] === $message['clientMessageId']) {
                return ['message' => $stored, 'inserted' => false];
            }
        }

        foreach ($this->messages[$chatId] ?? [] as $parent) {
            if ($parent['id'] === $parentMessageId) {
                $message['text'] = \alina\Services\Chat\ChatReply::compose($parent, $message['text']);

                return $this->appendMessage($chatId, $message);
            }
        }

        throw new RuntimeException('Message not found.');
    }

    public function editMessage(int $chatId, int $messageId, string $text): array
    {
        foreach ($this->messages[$chatId] ?? [] as $index => $message) {
            if ($message['id'] === $messageId) {
                $this->messages[$chatId][$index]['text'] = \alina\Services\Chat\ChatReply::replaceOwnText($message['text'], $text);

                return $this->messages[$chatId][$index];
            }
        }

        throw new RuntimeException('Message not found.');
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        foreach ($this->messages[$chatId] ?? [] as $index => $message) {
            if ($message['id'] === $messageId) {
                array_splice($this->messages[$chatId], $index, 1);

                return true;
            }
        }

        return false;
    }

    public function createDiscussion(int $chatId, int $messageId): array
    {
        throw new RuntimeException('Discussion behavior is covered by ChatDiscussionStorageTest.');
    }

    public function discussionForMessage(int $chatId, int $messageId): ?array
    {
        return null;
    }

    public function discussionParent(int $chatId): ?array
    {
        return null;
    }

    public function deleteChat(string $origin, string $channel): array
    {
        throw new RuntimeException('Chat deletion is covered by ChatDeletionStorageTest.');
    }

    public function renameChat(int $chatId, string $name, int $updatedAt): array
    {
        foreach ($this->chats as &$chat) {
            if ($chat['id'] === $chatId) {
                $chat['name'] = $name;
                $chat['updatedAt'] = $updatedAt;

                return $chat;
            }
        }

        throw new RuntimeException('Unknown chat.');
    }

    public function renameGuest(int $chatId, string $guestId, string $name): int
    {
        $updated = 0;
        foreach ($this->messages[$chatId] as &$message) {
            if (in_array($message['senderType'], ['guest', 'user_service'], true) && $message['senderId'] === $guestId) {
                $message['senderName'] = $name;
                $updated++;
            }
        }
        unset($message);
        return $updated;
    }

    public function setAnonymousAllowed(int $chatId, bool $allowed, int $updatedAt): array
    {
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
        $types = $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'];
        $before = count($this->messages[$chatId] ?? []);
        $this->messages[$chatId] = array_values(array_filter(
            $this->messages[$chatId] ?? [],
            static fn (array $message): bool => ! (
                in_array($message['senderType'], $types, true)
                && $message['senderId'] === $senderId
            ),
        ));

        return $before - count($this->messages[$chatId]);
    }
}

function expectSame(mixed $expected, mixed $actual, string $message): void
{
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            "FAIL: %s\nExpected: %s\nActual: %s",
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function storageMessage(
    string $clientMessageId,
    string $text,
    string $senderType = 'user',
    string $senderId = '17',
    int $createdAt = 1,
): array {
    return [
        'senderType' => $senderType,
        'senderId' => $senderId,
        'senderName' => $senderType === 'bot' ? 'Robocop' : 'Alice',
        'text' => $text,
        'createdAt' => $createdAt,
        'clientMessageId' => $clientMessageId,
    ];
}

$storage = new ContractMemoryChatStorage();
$chat = $storage->openChat('https://zero.home:50443', 'general');
expectSame('general', $chat['name'], 'new chat name must default to its channel');
expectSame(false, $storage->hasAnyMessage($chat['id']), 'new chat must have no history');
expectSame(1, $storage->listRooms('https://zero.home:50443', 'user', '17', [
    'mine' => ['limit' => 10, 'offset' => 0],
    'others' => ['limit' => 10, 'offset' => 0],
])['others']['total'], 'storage contract must expose room directory sections');
expectSame('PRIVATE: Alice & Bob', $storage->openChat('https://zero.home:50443', '1_2', 'PRIVATE: Alice & Bob')['name'], 'initial name must be used when creating a chat');
expectSame('PRIVATE: Alice & Bob', $storage->openChat('https://zero.home:50443', '1_2', 'PRIVATE: Changed & Name')['name'], 'later initial names must not overwrite an existing chat');

$first = $storage->appendMessage($chat['id'], storageMessage('first', 'one', createdAt: 100));
$second = $storage->appendMessage($chat['id'], storageMessage('second', 'two', createdAt: 200));
expectSame(true, $first['inserted'], 'first client message ID must insert');
expectSame(['one', 'two'], array_column($storage->history($chat['id']), 'text'), 'history must be oldest-to-newest');
expectSame(true, $storage->hasAnyMessage($chat['id']), 'stored messages must make chat non-empty');
expectSame(true, $storage->hasParticipantMessage($chat['id'], 'user', '17'), 'human message must establish participation');

$duplicate = $storage->appendMessage($chat['id'], storageMessage('second', 'changed retry', createdAt: 300));
expectSame(false, $duplicate['inserted'], 'duplicate client message ID must not insert');
expectSame($second['message'], $duplicate['message'], 'duplicate must return the originally stored message');
expectSame(2, count($storage->history($chat['id'])), 'duplicate must not grow history');

$storage->appendMessage($chat['id'], storageMessage('robot', 'system', 'bot', 'robocop', 400));
expectSame(false, $storage->hasParticipantMessage($chat['id'], 'bot', 'robocop'), 'bot message must never establish participation');
$storage->appendMessage($chat['id'], storageMessage('service-22', 'mention', 'user_service', '22', 450));
expectSame(['user:22', 'user:17'], array_map(fn (array $person): string => $person['senderType'] . ':' . $person['senderId'], $storage->participantSnapshots($chat['id'])), 'service mention must establish retained participation alongside ordinary authors');

$renamed = $storage->renameChat($chat['id'], 'Humans only', 500);
expectSame('Humans only', $renamed['name'], 'rename must return the new name');
expectSame(500, $renamed['updatedAt'], 'rename must return the supplied timestamp');

$deleted = $storage->deleteParticipantMessages($chat['id'], 'user', '17');
expectSame(2, $deleted, 'participant deletion must remove only that participant messages');
expectSame(['system', 'mention'], array_column($storage->history($chat['id']), 'text'), 'participant deletion must retain bot and other participant messages');
$deletedMention = $storage->deleteParticipantMessages($chat['id'], 'user', '22');
expectSame(1, $deletedMention, 'participant deletion must remove service mention evidence belonging to the target');

for ($index = 0; $index < 1001; $index++) {
    $storage->appendMessage($chat['id'], storageMessage('bulk-' . $index, 'bulk ' . $index, createdAt: 1000 + $index));
}

$retained = $storage->history($chat['id'], 2000);
expectSame(1000, count($retained), 'chat must retain at most 1,000 messages');
expectSame('bulk 1', $retained[0]['text'], 'pruning must remove the oldest message first');
expectSame('bulk 1000', $retained[999]['text'], 'pruning must retain the newest message');

echo "PASS: chat storage contract preserves history, identity, idempotency, pruning, rename and deletion\n";
