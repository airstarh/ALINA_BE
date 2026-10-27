<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Chat/ChatReply.php';
require_once __DIR__ . '/../../Services/Chat/ChatMentions.php';
require_once __DIR__ . '/../../Services/Chat/ChatDiscussion.php';
require_once __DIR__ . '/../../Services/Chat/ChatForward.php';

use alina\Services\Chat\ChatStorage;
use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;

final class MemoryChatStorage implements ChatStorage
{
    private int $nextChatId = 1;
    private int $nextMessageId = 1;
    private array $chats = [];
    private array $messages = [];
    private array $reactions = [];
    private array $chatPins = [];
    private array $registeredUsers = [];
    public ?Throwable $nextError = null;

    public function openChat(string $origin, string $channel, ?string $initialName = null, bool $private = false): array
    {
        $this->maybeFail();
        $key = $this->chatKey($origin, $channel);

        if (! isset($this->chats[$key])) {
            $now = $this->now();
            $this->chats[$key] = [
                'id' => $this->nextChatId++,
                'origin' => $origin,
                'channel' => $channel,
                'name' => $initialName ?? $channel,
                'isAnionAllowed' => ! $private,
                'isConfirmationRequired' => $private,
                'discussionOfMessageId' => null,
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
            $isMentioned = false;
            $latestMentionAt = null;

            foreach ($this->messages[$chat['id']] ?? [] as $message) {
                $latestMessageAt = max($latestMessageAt ?? $message['createdAt'], $message['createdAt']);

                if ($message['senderType'] === 'bot') {
                    continue;
                }

                $participants[$message['senderId']] = true;
                $participated = $participated || (
                    in_array($message['senderType'], $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'], true)
                    && $message['senderId'] === $senderId
                );
                $isMentioned = $isMentioned || (
                    $message['senderType'] === 'user_service'
                    && $message['senderId'] === $senderId
                    && str_starts_with($message['text'], 'MENTION:')
                );
                if ($message['senderType'] === 'user_service' && $message['senderId'] === $senderId && str_starts_with($message['text'], 'MENTION:')) {
                    $latestMentionAt = max($latestMentionAt ?? $message['createdAt'], $message['createdAt']);
                }
            }

            $row = [
                'id' => $chat['id'],
                'channel' => $chat['channel'],
                'chatName' => $chat['name'],
                'participantCount' => count($participants),
                'latestMessageAt' => $latestMessageAt,
                'isMentioned' => $isMentioned,
                'latestMentionAt' => $latestMentionAt,
                'isPinned' => isset($this->chatPins[$senderId . ':' . $chat['id']]),
                'messageCount' => min(5001, count(array_filter($this->messages[$chat['id']] ?? [], fn ($message) => $message['senderType'] === 'user'))),
            ];
            if ($participated || $row['isPinned']) {
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

    public function searchRooms(string $origin, string $senderId, string $query, int $limit): array
    {
        $this->maybeFail();
        $rows = [];
        foreach ($this->chats as $chat) {
            if ($chat['origin'] !== $origin || mb_stripos($chat['name'], $query) === false) continue;
            $messages = $this->messages[$chat['id']] ?? [];
            $participants = [];
            foreach ($messages as $message) {
                if ($message['senderType'] !== 'bot') $participants[$message['senderType'] . ':' . $message['senderId']] = true;
            }
            $rows[] = [
                'id' => $chat['id'], 'channel' => $chat['channel'], 'chatName' => $chat['name'],
                'participantCount' => count($participants),
                'latestMessageAt' => $messages ? max(array_column($messages, 'createdAt')) : null,
                'isMentioned' => false, 'latestMentionAt' => null,
                'isPinned' => isset($this->chatPins[$senderId . ':' . $chat['id']]),
                'messageCount' => min(5001, count(array_filter($messages, fn ($message) => $message['senderType'] === 'user'))),
            ];
        }
        usort($rows, static fn (array $a, array $b): int => ($b['latestMessageAt'] ?? 0) <=> ($a['latestMessageAt'] ?? 0));
        return array_slice($rows, 0, $limit);
    }

    public function toggleChatPin(string $origin, string $channel, string $userId, string $userName, string $userEmblem, int $createdAt): bool
    {
        $chat = $this->chats[$this->chatKey($origin, $channel)] ?? null;
        if ($chat === null) throw new RuntimeException('Chat not found.');
        $key = $userId . ':' . $chat['id'];
        if (isset($this->chatPins[$key])) { unset($this->chatPins[$key]); return false; }
        $this->chatPins[$key] = $createdAt;
        return true;
    }

    public function mentionCandidates(string $origin, string $senderType, string $senderId, string $query, int $limit): array
    {
        $rows = [];
        foreach ($this->chats as $chat) {
            if ($chat['origin'] !== $origin) continue;
            foreach ($this->messages[$chat['id']] ?? [] as $message) {
                if (! in_array($message['senderType'], ['user', 'guest', 'user_service'], true) || $message['senderId'] === $senderId) continue;
                $rows[] = $message;
            }
        }
        usort($rows, fn ($a, $b) => $b['createdAt'] <=> $a['createdAt']);
        $result = [];
        foreach ($rows as $row) {
            $type = $row['senderType'] === 'user_service' ? (ctype_digit($row['senderId']) ? 'user' : 'guest') : $row['senderType'];
            if (! isset($result[$row['senderId']])) {
                $result[$row['senderId']] = ['senderType' => $type, 'senderId' => $row['senderId'], 'senderName' => $row['senderName'], 'senderEmblem' => $row['senderEmblem'], 'latestActivityAt' => $row['createdAt'], '_genuine' => $row['senderType'] !== 'user_service'];
            }
            elseif (! $result[$row['senderId']]['_genuine'] && $row['senderType'] !== 'user_service') {
                $result[$row['senderId']] = array_replace($result[$row['senderId']], ['senderType' => $type, 'senderName' => $row['senderName'], 'senderEmblem' => $row['senderEmblem'], '_genuine' => true]);
            }
        }
        $result = array_values(array_filter($result, fn ($candidate) => $query === '' || str_contains(mb_strtolower($candidate['senderName']), mb_strtolower($query))));
        foreach ($result as &$candidate) unset($candidate['_genuine']);
        return array_slice($result, 0, $limit);
    }

    public function participantSnapshots(int $chatId): array
    {
        $result = [];
        foreach (array_reverse($this->messages[$chatId] ?? []) as $message) {
            if (! in_array($message['senderType'], ['user', 'guest', 'user_service'], true)) continue;
            $type = $message['senderType'] === 'user_service'
                ? (ctype_digit($message['senderId']) ? 'user' : 'guest')
                : $message['senderType'];
            $key = $type . ':' . $message['senderId'];
            $result[$key] ??= [
                'senderType' => $type, 'senderId' => $message['senderId'],
                'senderName' => $message['senderName'], 'senderEmblem' => $message['senderEmblem'],
            ];
        }
        return array_values($result);
    }

    public function searchUsers(string $query, int $limit): array
    {
        $result = [];
        foreach (array_reverse(array_merge(...array_values($this->messages ?: [[]]))) as $message) {
            if (! in_array($message['senderType'], ['user', 'guest', 'user_service'], true)) continue;
            $type = $message['senderType'] === 'user_service' ? (ctype_digit($message['senderId']) ? 'user' : 'guest') : $message['senderType'];
            $key = $type . ':' . $message['senderId'];
            if (! isset($result[$key]) && mb_stripos($message['senderName'], $query) !== false) $result[$key] = ['senderType'=>$type,'senderId'=>$message['senderId'],'senderName'=>$message['senderName'],'senderEmblem'=>$message['senderEmblem']];
        }
        return array_slice(array_values($result), 0, $limit);
    }

    public function registerUser(string $id, string $name, string $emblem = ''): void
    {
        $this->registeredUsers[$id] = ['senderType'=>'user', 'senderId'=>$id, 'senderName'=>$name, 'senderEmblem'=>$emblem];
    }

    public function forwardCandidates(string $origin, string $senderId, string $query, int $limit): array
    {
        $users = array_values(array_filter($this->registeredUsers, fn ($user) => $user['senderId'] !== $senderId && mb_stripos($user['senderName'], $query) !== false));
        usort($users, fn ($a, $b) => $a['senderId'] <=> $b['senderId']);
        return ['users'=>array_slice($users, 0, $limit), 'rooms'=>$this->searchRooms($origin, $senderId, $query, $limit)];
    }

    public function forwardMessage(int $sourceChatId, int $sourceMessageId, string $origin, array $request): array
    {
        $source = null;
        foreach ($this->messages[$sourceChatId] ?? [] as $message) if ($message['id'] === $sourceMessageId) $source = $message;
        if ($source === null) throw new RuntimeException('Message not found.');
        $sourceChat = null;
        foreach ($this->chats as $chat) if ($chat['id'] === $sourceChatId && $chat['origin'] === $origin) $sourceChat = $chat;
        if ($sourceChat === null) throw new RuntimeException('Message not found.');

        if (($request['targetType'] ?? '') === 'user') {
            $target = $this->registeredUsers[(string) ($request['targetId'] ?? '')] ?? null;
            if ($target === null || $target['senderId'] === $request['senderId']) throw new RuntimeException('Forward destination not found.');
            $ids = [(string) $request['senderId'], $target['senderId']];
            usort($ids, fn ($a, $b) => strlen($a) <=> strlen($b) ?: strcmp($a, $b));
            $channel = implode('_', $ids);
            $names = [(string) $request['senderId'] => $request['senderName'], $target['senderId'] => $target['senderName']];
            $chat = $this->openChat($origin, $channel, 'PRIVATE: ' . $names[$ids[0]] . ' & ' . $names[$ids[1]], true);
        } else {
            $chat = $this->chats[$this->chatKey($origin, (string) ($request['targetId'] ?? ''))] ?? null;
            if ($chat === null) throw new RuntimeException('Forward destination not found.');
        }
        $stored = $this->appendMessage($chat['id'], [
            'senderType'=>'user', 'senderId'=>(string) $request['senderId'], 'senderName'=>(string) $request['senderName'],
            'senderEmblem'=>(string) $request['senderEmblem'],
            'text'=>\alina\Services\Chat\ChatForward::compose($source + ['channel'=>$sourceChat['channel']]),
            'createdAt'=>(int) $request['createdAt'], 'clientMessageId'=>(string) $request['clientMessageId'],
        ]);
        return $stored + ['chat'=>$chat];
    }

    public function configureChat(string $origin, string $channel, array $changes): array
    {
        $this->openChat($origin, $channel);
        $key = $this->chatKey($origin, $channel);
        $this->chats[$key] = array_replace($this->chats[$key], $changes);

        return $this->chats[$key];
    }

    public function history(int $chatId, int $limit = 200, ?int $beforeMessageId = null): array
    {
        $this->maybeFail();
        $messages = array_values(array_filter(
            $this->messages[$chatId] ?? [],
            static fn (array $message): bool => $beforeMessageId === null || $message['id'] < $beforeMessageId,
        ));
        return array_slice($messages, -$limit);
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
                in_array($message['senderType'], $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'], true)
                && $message['senderId'] === $senderId
            ) {
                return true;
            }
        }

        return false;
    }

    public function toggleReaction(int $chatId, int $messageId, string $senderType, string $senderId, int $reaction, int $updatedAt): void
    {
        $found = false;
        foreach ($this->messages[$chatId] ?? [] as $message) if ($message['id'] === $messageId) $found = true;
        if (! $found) throw new RuntimeException('Message not found.');
        $key = $senderType . ':' . $senderId;
        if (($this->reactions[$messageId][$key]['reaction'] ?? null) === $reaction) unset($this->reactions[$messageId][$key]);
        else $this->reactions[$messageId][$key] = ['senderType' => $senderType, 'senderId' => $senderId, 'reaction' => $reaction, 'updatedAt' => $updatedAt];
    }

    public function reactionSummary(int $chatId, int $messageId, string $senderType, string $senderId): array
    {
        $rows = $this->reactions[$messageId] ?? [];
        return [
            'positiveCount' => count(array_filter($rows, fn ($row) => $row['reaction'] === 1)),
            'negativeCount' => count(array_filter($rows, fn ($row) => $row['reaction'] === -1)),
            'currentReaction' => $rows[$senderType . ':' . $senderId]['reaction'] ?? 0,
        ];
    }

    public function reactionSummaries(int $chatId, array $messageIds, string $senderType, string $senderId): array
    {
        $result = [];
        foreach ($messageIds as $messageId) $result[$messageId] = $this->reactionSummary($chatId, $messageId, $senderType, $senderId);
        return $result;
    }

    public function reactionPeople(int $chatId, int $messageId): array
    {
        $result = ['positive' => [], 'negative' => []];
        foreach ($this->reactions[$messageId] ?? [] as $row) {
            $name = $row['senderType'] . ':' . $row['senderId'];
            $emblem = '';
            foreach (array_reverse($this->messages[$chatId] ?? []) as $message) {
                if ($message['senderId'] === $row['senderId'] && in_array($message['senderType'], [$row['senderType'], 'user_service'], true)) {
                    $name = $message['senderName']; $emblem = $message['senderEmblem']; break;
                }
            }
            $result[$row['reaction'] === 1 ? 'positive' : 'negative'][] = ['senderType' => $row['senderType'], 'senderId' => $row['senderId'], 'name' => $name, 'emblem' => $emblem];
        }
        return $result;
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
        $discussion = false;
        foreach ($this->chats as $chat) if ($chat['id'] === $chatId) $discussion = $chat['discussionOfMessageId'] !== null;
        if ($discussion && count($this->messages[$chatId]) > 1000) {
            $this->messages[$chatId] = [$this->messages[$chatId][0], ...array_slice($this->messages[$chatId], -999)];
        } else {
            $this->messages[$chatId] = array_slice($this->messages[$chatId], -1000);
        }

        return ['message' => $stored, 'inserted' => true];
    }

    public function appendMessageGroup(int $chatId, array $message, array $targets): array
    {
        foreach ($this->messages[$chatId] ?? [] as $stored) {
            if ($stored['clientMessageId'] === $message['clientMessageId'] && $stored['senderType'] !== 'user_service') {
                return ['messages' => array_values(array_filter($this->messages[$chatId], fn ($row) => $row['clientMessageId'] === $message['clientMessageId'])), 'inserted' => false];
            }
        }
        $messages = [];
        foreach ([...$targets, $message] as $item) {
            $stored = ['id' => $this->nextMessageId++] + $item;
            $this->messages[$chatId][] = $stored;
            $messages[] = $stored;
        }
        $this->messages[$chatId] = array_slice($this->messages[$chatId], -1000);
        return ['messages' => $messages, 'inserted' => true];
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

        foreach ($this->chats as $chat) {
            if ($chat['discussionOfMessageId'] === $messageId) {
                throw new RuntimeException('A message with a discussion cannot be deleted.');
            }
            if ($chat['id'] === $chatId && $chat['discussionOfMessageId'] !== null) {
                $seedId = $this->messages[$chatId][0]['id'] ?? null;
                if ($seedId === $messageId) throw new RuntimeException('A discussion seed cannot be deleted.');
            }
        }

        foreach ($this->messages[$chatId] ?? [] as $index => $message) {
            if ($message['id'] === $messageId && !($message['isPinned'] ?? false)) {
                array_splice($this->messages[$chatId], $index, 1);

                return true;
            }
        }

        return false;
    }

    public function toggleMessagePin(int $chatId, int $messageId): ?array
    {
        $foundIndex = null;
        $pin = false;
        foreach ($this->messages[$chatId] ?? [] as $index => $message) {
            if ($message['id'] === $messageId) { $foundIndex = $index; $pin = !($message['isPinned'] ?? false); }
        }
        if ($foundIndex === null) return null;
        foreach ($this->messages[$chatId] as &$message) $message['isPinned'] = false;
        unset($message);
        if ($pin) $this->messages[$chatId][$foundIndex]['isPinned'] = true;
        return $pin ? $this->messages[$chatId][$foundIndex] : null;
    }

    public function pinnedMessage(int $chatId): ?array
    {
        foreach ($this->messages[$chatId] ?? [] as $message) if ($message['isPinned'] ?? false) return $message;
        return null;
    }

    public function createDiscussion(int $chatId, int $messageId): array
    {
        $this->maybeFail();
        $source = null;
        foreach ($this->messages[$chatId] ?? [] as $message) {
            if ($message['id'] === $messageId) $source = $message;
        }
        if ($source === null) throw new RuntimeException('Message not found.');

        foreach ($this->chats as $chat) {
            if ($chat['discussionOfMessageId'] === $messageId) {
                return [
                    'chat' => $chat,
                    'seedMessage' => $this->messages[$chat['id']][0],
                    'responseCount' => max(0, count($this->messages[$chat['id']] ?? []) - 1),
                    'created' => false,
                ];
            }
        }

        $parent = null;
        foreach ($this->chats as $chat) if ($chat['id'] === $chatId) $parent = $chat;
        if ($parent === null) throw new RuntimeException('Chat not found.');
        $child = $this->openChat($parent['origin'], 'discussion_' . $messageId);
        $key = $this->chatKey($parent['origin'], $child['channel']);
        $this->chats[$key]['name'] = \alina\Services\Chat\ChatDiscussion::title($messageId, $source['text']);
        $this->chats[$key]['discussionOfMessageId'] = $messageId;
        $child = $this->chats[$key];
        $seed = $this->appendMessage($child['id'], array_replace($source, [
            'clientMessageId' => 'discussion-seed-' . $messageId,
        ]))['message'];

        return ['chat' => $child, 'seedMessage' => $seed, 'responseCount' => 0, 'created' => true];
    }

    public function discussionForMessage(int $chatId, int $messageId): ?array
    {
        foreach ($this->messages[$chatId] ?? [] as $message) {
            if ($message['id'] !== $messageId) continue;
            foreach ($this->chats as $chat) {
                if ($chat['discussionOfMessageId'] === $messageId) {
                    return [
                        'channel' => $chat['channel'],
                        'responseCount' => max(0, count($this->messages[$chat['id']] ?? []) - 1),
                    ];
                }
            }
        }
        return null;
    }

    public function discussionParent(int $chatId): ?array
    {
        $child = null;
        foreach ($this->chats as $chat) if ($chat['id'] === $chatId) $child = $chat;
        if (($child['discussionOfMessageId'] ?? null) === null) return null;
        foreach ($this->chats as $parent) {
            foreach ($this->messages[$parent['id']] ?? [] as $message) {
                if ($message['id'] === $child['discussionOfMessageId']) return [
                    'origin' => $parent['origin'], 'channel' => $parent['channel'],
                    'sourceMessageId' => $message['id'],
                    'responseCount' => max(0, count($this->messages[$chatId] ?? []) - 1),
                ];
            }
        }
        return null;
    }

    public function deleteChat(string $origin, string $channel): array
    {
        $key = $this->chatKey($origin, $channel);
        if (!isset($this->chats[$key])) throw new RuntimeException('Chat not found.');
        $restored = null;
        $root = $this->chats[$key];
        if ($root['discussionOfMessageId'] !== null) {
            foreach ($this->chats as $parent) foreach ($this->messages[$parent['id']] ?? [] as $message) {
                if ($message['id'] === $root['discussionOfMessageId']) $restored = ['origin'=>$parent['origin'],'channel'=>$parent['channel'],'sourceMessageId'=>$message['id']];
            }
        }
        $channels = [];
        $remove = function (array $room) use (&$remove, &$channels): void {
            $messageIds = array_column($this->messages[$room['id']] ?? [], 'id');
            foreach (array_values($this->chats) as $candidate) {
                if ($candidate['discussionOfMessageId'] !== null && in_array($candidate['discussionOfMessageId'], $messageIds, true)) $remove($candidate);
            }
            $channels[] = $room['channel'];
            unset($this->messages[$room['id']], $this->chats[$this->chatKey($room['origin'], $room['channel'])]);
        };
        $remove($root);
        return ['channels' => $channels, 'restored' => $restored];
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
