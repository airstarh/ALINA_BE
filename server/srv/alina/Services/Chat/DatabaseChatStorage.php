<?php

declare(strict_types=1);

namespace alina\Services\Chat;

use alina\mvc\Model\chat;
use alina\mvc\Model\chat_message;
use alina\mvc\Model\chat_user;
use Illuminate\Database\Capsule\Manager as Dal;
use RuntimeException;
use stdClass;
use Throwable;

final class DatabaseChatStorage implements ChatStorage
{
    public function openChat(string $origin, string $channel, ?string $initialName = null, bool $private = false): array
    {
        $row = (new chat())->findByOriginAndChannel($origin, $channel);

        if ($row === null) {
            try {
                $row = (new chat())->insertForChannel($origin, $channel, $initialName ?? $channel, ! $private, $private);
            }
            catch (Throwable $error) {
                $row = (new chat())->findByOriginAndChannel($origin, $channel);

                if ($row === null) {
                    throw $error;
                }
            }
        }

        return $this->normalizeChat($row);
    }

    public function listRooms(string $origin, string $senderType, string $senderId, array $pagination): array
    {
        $model = new chat();
        $result = [];

        foreach (['mine' => true, 'others' => false] as $name => $participated) {
            $page = $pagination[$name];
            $result[$name] = [
                'rows' => array_map(
                    fn (stdClass $row): array => [
                        'id' => (int) $row->id,
                        'channel' => (string) $row->channel_name_pseudo_id,
                        'chatName' => (string) $row->chat_name_human,
                        'participantCount' => (int) ($row->participant_count ?? 0),
                        'latestMessageAt' => $row->latest_message_at === null ? null : (int) $row->latest_message_at,
                        'isMentioned' => (bool) ($row->is_mentioned ?? false),
                        'latestMentionAt' => $row->latest_mention_at === null ? null : (int) $row->latest_mention_at,
                        'isPinned' => (bool) ($row->is_pinned ?? false),
                        'messageCount' => $this->userMessageCount((int) $row->id),
                    ],
                    $model->directoryRows($origin, $senderType, $senderId, $participated, $page['limit'], $page['offset']),
                ),
                'total' => $model->directoryCount($origin, $senderType, $senderId, $participated),
                'limit' => $page['limit'],
                'offset' => $page['offset'],
            ];
        }

        return $result;
    }

    public function searchRooms(string $origin, string $senderId, string $query, int $limit): array
    {
        return array_map(fn (stdClass $row): array => [
            'id' => (int) $row->id,
            'channel' => (string) $row->channel_name_pseudo_id,
            'chatName' => (string) $row->chat_name_human,
            'participantCount' => (int) ($row->participant_count ?? 0),
            'latestMessageAt' => $row->latest_message_at === null ? null : (int) $row->latest_message_at,
            'isMentioned' => false,
            'latestMentionAt' => null,
            'isPinned' => (bool) ($row->is_pinned ?? false),
            'messageCount' => $this->userMessageCount((int) $row->id),
        ], (new chat())->searchRows($origin, $senderId, $query, $limit));
    }

    public function forwardCandidates(string $origin, string $senderId, string $query, int $limit): array
    {
        $pattern = '%' . addcslashes($query, '%_\\') . '%';
        $users = Dal::table('user')
            ->where('id', '!=', $senderId)
            ->where(fn ($active) => $active->whereNull('is_deleted')->orWhere('is_deleted', '=', 0))
            ->whereRaw("TRIM(CONCAT_WS(' ', firstname, lastname)) LIKE ? ESCAPE '\\\\'", [$pattern])
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'firstname', 'lastname', 'mail', 'emblem'])
            ->map(static function (stdClass $row): array {
                $name = trim(implode(' ', array_filter([(string) ($row->firstname ?? ''), (string) ($row->lastname ?? '')])));
                return [
                    'senderType' => 'user',
                    'senderId' => (string) $row->id,
                    'senderName' => $name !== '' ? $name : (string) $row->mail,
                    'senderEmblem' => (string) ($row->emblem ?? ''),
                ];
            })
            ->all();

        return ['users' => $users, 'rooms' => $this->searchRooms($origin, $senderId, $query, $limit)];
    }

    public function forwardMessage(int $sourceChatId, int $sourceMessageId, string $origin, array $request): array
    {
        if (($request['senderType'] ?? null) !== 'user') throw new RuntimeException('Only registered users can forward messages.');

        $sourceChat = Dal::table('chat')->where('id', '=', $sourceChatId)->where('origin', '=', $origin)->first();
        $source = (new chat_message())->findForChat($sourceChatId, $sourceMessageId);
        if ($sourceChat === null || $source === null) throw new RuntimeException('Message not found.');

        $targetType = (string) ($request['targetType'] ?? '');
        $targetId = (string) ($request['targetId'] ?? '');
        if ($targetType === 'user') {
            $target = Dal::table('user')
                ->where('id', '=', $targetId)
                ->where(fn ($active) => $active->whereNull('is_deleted')->orWhere('is_deleted', '=', 0))
                ->first(['id', 'firstname', 'lastname', 'mail']);
            if ($target === null || $targetId === (string) $request['senderId']) throw new RuntimeException('Forward recipient not found.');
            $ids = [(string) $request['senderId'], $targetId];
            usort($ids, static fn (string $a, string $b): int => strlen($a) <=> strlen($b) ?: strcmp($a, $b));
            $targetName = trim(implode(' ', array_filter([(string) ($target->firstname ?? ''), (string) ($target->lastname ?? '')])));
            $names = [
                (string) $request['senderId'] => (string) $request['senderName'],
                $targetId => $targetName !== '' ? $targetName : (string) $target->mail,
            ];
            $chat = $this->openChat($origin, implode('_', $ids), 'PRIVATE: ' . $names[$ids[0]] . ' & ' . $names[$ids[1]], true);
        }
        elseif ($targetType === 'chat') {
            $row = (new chat())->findByOriginAndChannel($origin, $targetId);
            if ($row === null) throw new RuntimeException('Forward chat not found.');
            $chat = $this->normalizeChat($row);
        }
        else throw new RuntimeException('Invalid forward destination.');

        $normalizedSource = $this->normalizeMessage($source);
        $normalizedSource['channel'] = (string) $sourceChat->channel_name_pseudo_id;
        $stored = $this->appendMessage((int) $chat['id'], [
            'senderType' => 'user',
            'senderId' => (string) $request['senderId'],
            'senderName' => (string) $request['senderName'],
            'senderEmblem' => (string) $request['senderEmblem'],
            'text' => ChatForward::compose($normalizedSource),
            'createdAt' => (int) $request['createdAt'],
            'clientMessageId' => (string) $request['clientMessageId'],
        ]);

        return $stored + ['chat' => $chat];
    }

    public function toggleChatPin(string $origin, string $channel, string $userId, string $userName, string $userEmblem, int $createdAt): bool
    {
        return Dal::connection()->transaction(function () use ($origin, $channel, $userId, $userName, $userEmblem, $createdAt): bool {
            $room = (new chat())->findByOriginAndChannel($origin, $channel);
            if ($room === null) throw new RuntimeException('Chat not found.');
            $identity = (new chat_user())->upsertIdentity('user', $userId, $userName, $userEmblem, $createdAt);
            $query = Dal::table('chat_pinned')->where('chat_user_id', '=', $identity->id)->where('chat_id', '=', $room->id);
            if ($query->exists()) { $query->delete(); return false; }
            Dal::table('chat_pinned')->insert(['chat_user_id' => $identity->id, 'chat_id' => $room->id, 'created_at' => $createdAt]);
            return true;
        });
    }

    public function mentionCandidates(string $origin, string $senderType, string $senderId, string $query, int $limit): array
    {
        $pattern = '%' . addcslashes($query, '%_\\') . '%';
        $rows = Dal::table('chat_message')
            ->join('chat_user', 'chat_user.id', '=', 'chat_message.chat_user_id')
            ->join('chat', 'chat.id', '=', 'chat_message.chat_id')
            ->where('chat.origin', '=', $origin)
            ->whereIn('chat_message.sender_type', ['user', 'guest', 'user_service'])
            ->when($query !== '', fn ($matching) => $matching->whereRaw("chat_user.user_name LIKE ? ESCAPE '\\\\'", [$pattern]))
            ->orderBy('chat_message.created_at', 'DESC')
            ->orderBy('chat_message.id', 'DESC')
            ->get(['chat_message.sender_type', 'chat_user.user_type', 'chat_user.user_id', 'chat_user.user_name', 'chat_user.user_emblem', 'chat_message.created_at']);
        $result = [];
        foreach ($rows as $row) {
            $id = (string) $row->user_id;
            $type = (string) $row->user_type;
            if ($id === $senderId && $type === $senderType) continue;
            $key = $type . ':' . $id;
            $result[$key] ??= ['senderType' => $type, 'senderId' => $id, 'senderName' => (string) $row->user_name, 'senderEmblem' => (string) $row->user_emblem, 'latestActivityAt' => (int) $row->created_at];
        }
        $result = array_values(array_filter($result, fn ($candidate) => $query === '' || mb_stripos($candidate['senderName'], $query) !== false));
        return array_slice($result, 0, $limit);
    }

    public function participantSnapshots(int $chatId): array
    {
        $result = [];
        foreach (array_reverse((new chat_message())->latestForChat($chatId, 1000)) as $row) {
            if (! in_array($row->sender_type, ['user', 'guest', 'user_service'], true)) continue;
            $senderId = (string) $row->identity_sender_id;
            $senderType = $row->sender_type === 'user_service'
                ? ChatUserIdentity::type('user_service', $senderId)
                : (string) $row->sender_type;
            $key = $senderType . ':' . $senderId;
            $result[$key] ??= [
                'senderType' => $senderType,
                'senderId' => $senderId,
                'senderName' => (string) $row->identity_sender_name,
                'senderEmblem' => (string) $row->identity_sender_emblem,
            ];
        }
        return array_values($result);
    }

    public function searchUsers(string $query, int $limit): array
    {
        $pattern = '%' . addcslashes($query, '%_\\') . '%';
        return array_map(static fn (stdClass $row): array => [
            'senderType' => (string) $row->user_type,
            'senderId' => (string) $row->user_id,
            'senderName' => (string) $row->user_name,
            'senderEmblem' => (string) $row->user_emblem,
        ], Dal::table('chat_user')->whereIn('user_type', ['user', 'guest'])
            ->whereRaw("user_name LIKE ? ESCAPE '\\\\'", [$pattern])->orderBy('user_name')->limit($limit)->get()->all());
    }

    public function history(int $chatId, int $limit = 200, ?int $beforeMessageId = null): array
    {
        return array_map(
            fn (stdClass $row): array => $this->normalizeMessage($row),
            (new chat_message())->latestForChat($chatId, $limit, $beforeMessageId),
        );
    }

    public function hasAnyMessage(int $chatId): bool
    {
        return (new chat_message())->hasAnyForChat($chatId);
    }

    public function hasParticipantMessage(int $chatId, string $senderType, string $senderId): bool
    {
        return (new chat_message())->hasParticipantMessage($chatId, $senderType, $senderId);
    }

    public function toggleReaction(int $chatId, int $messageId, string $senderType, string $senderId, int $reaction, int $updatedAt): void
    {
        Dal::connection()->transaction(function () use ($chatId, $messageId, $senderType, $senderId, $reaction, $updatedAt): void {
            $message = Dal::table('chat_message')->where('chat_id', '=', $chatId)->where('id', '=', $messageId)->lockForUpdate()->first();
            $user = Dal::table('chat_user')->where('user_type', '=', $senderType)->where('user_id', '=', $senderId)->first();
            if ($message === null || $user === null) throw new RuntimeException('Message or chat user not found.');
            $existing = Dal::table('chat_like')->where('chat_message_id', '=', $messageId)->where('chat_user_id', '=', $user->id)->first();
            if ($existing !== null && (int) $existing->reaction === $reaction) {
                Dal::table('chat_like')->where('id', '=', $existing->id)->delete();
            }
            elseif ($existing !== null) {
                Dal::table('chat_like')->where('id', '=', $existing->id)->update(['reaction' => $reaction, 'updated_at' => $updatedAt]);
            }
            else {
                Dal::table('chat_like')->insert(['chat_message_id' => $messageId, 'chat_user_id' => $user->id, 'reaction' => $reaction, 'created_at' => $updatedAt, 'updated_at' => $updatedAt]);
            }
        });
    }

    public function reactionSummary(int $chatId, int $messageId, string $senderType, string $senderId): array
    {
        return $this->reactionSummaries($chatId, [$messageId], $senderType, $senderId)[$messageId];
    }

    public function reactionSummaries(int $chatId, array $messageIds, string $senderType, string $senderId): array
    {
        $messageIds = array_values(array_unique(array_map('intval', $messageIds)));
        $empty = ['positiveCount' => 0, 'negativeCount' => 0, 'currentReaction' => 0];
        $result = array_fill_keys($messageIds, $empty);
        if ($messageIds === []) return $result;
        $userId = Dal::table('chat_user')->where('user_type', '=', $senderType)->where('user_id', '=', $senderId)->value('id');
        $rows = Dal::table('chat_like')->join('chat_message', 'chat_message.id', '=', 'chat_like.chat_message_id')
            ->where('chat_message.chat_id', '=', $chatId)->whereIn('chat_like.chat_message_id', $messageIds)
            ->select('chat_like.chat_message_id')
            ->selectRaw('SUM(chat_like.reaction = 1) AS positive_count, SUM(chat_like.reaction = -1) AS negative_count')
            ->selectRaw('MAX(CASE WHEN chat_like.chat_user_id = ? THEN chat_like.reaction ELSE 0 END) AS current_reaction', [$userId ?? 0])
            ->groupBy('chat_like.chat_message_id')->get();
        foreach ($rows as $row) $result[(int) $row->chat_message_id] = ['positiveCount' => (int) $row->positive_count, 'negativeCount' => (int) $row->negative_count, 'currentReaction' => (int) $row->current_reaction];
        return $result;
    }

    public function reactionPeople(int $chatId, int $messageId): array
    {
        $rows = Dal::table('chat_like')->join('chat_message', 'chat_message.id', '=', 'chat_like.chat_message_id')
            ->join('chat_user', 'chat_user.id', '=', 'chat_like.chat_user_id')
            ->where('chat_message.chat_id', '=', $chatId)->where('chat_like.chat_message_id', '=', $messageId)
            ->orderBy('chat_like.updated_at')->get(['chat_like.reaction', 'chat_user.user_type', 'chat_user.user_id', 'chat_user.user_name', 'chat_user.user_emblem']);
        $result = ['positive' => [], 'negative' => []];
        foreach ($rows as $row) $result[(int) $row->reaction === 1 ? 'positive' : 'negative'][] = [
            'senderType' => (string) $row->user_type, 'senderId' => (string) $row->user_id,
            'name' => (string) $row->user_name, 'emblem' => (string) $row->user_emblem,
        ];
        return $result;
    }

    public function appendMessage(int $chatId, array $message): array
    {
        try {
            return Dal::connection()->transaction(function () use ($chatId, $message): array {
                Dal::table('chat')->where('id', '=', $chatId)->lockForUpdate()->first();
                $model = new chat_message();
                $row = $model->findByClientMessageId($chatId, $message['clientMessageId']);

                if ($row !== null) {
                    return ['message' => $this->normalizeMessage($row), 'inserted' => false];
                }

                $row = $this->insertMessage($model, $chatId, $message);
                (new chat_message())->pruneChat($chatId, 1000);

                return ['message' => $this->normalizeMessage($row), 'inserted' => true];
            });
        }
        catch (Throwable $error) {
            // A concurrent request may have won the unique key. This lookup
            // must happen after the failed transaction rolls back so a fresh
            // read can observe the committed row.
            $row = (new chat_message())->findByClientMessageId($chatId, $message['clientMessageId']);

            if ($row === null) {
                throw $error;
            }

            return ['message' => $this->normalizeMessage($row), 'inserted' => false];
        }
    }

    public function appendMessageGroup(int $chatId, array $message, array $targets): array
    {
        return Dal::connection()->transaction(function () use ($chatId, $message, $targets): array {
            Dal::table('chat')->where('id', '=', $chatId)->lockForUpdate()->first();
            $model = new chat_message();
            $existing = $model->findByClientMessageId($chatId, $message['clientMessageId']);
            if ($existing !== null) {
                return ['messages' => array_map(fn ($row) => $this->normalizeMessage($row), $model->findGroup($chatId, $message['clientMessageId'])), 'inserted' => false];
            }
            $stored = [];
            foreach ($targets as $target) $stored[] = $this->normalizeMessage($this->insertMessage($model, $chatId, $target));
            $stored[] = $this->normalizeMessage($this->insertMessage($model, $chatId, $message));
            $model->pruneChat($chatId, 1000);
            return ['messages' => $stored, 'inserted' => true];
        });
    }

    public function appendReply(int $chatId, int $parentMessageId, array $message): array
    {
        try {
            return Dal::connection()->transaction(function () use ($chatId, $parentMessageId, $message): array {
                Dal::table('chat')->where('id', '=', $chatId)->lockForUpdate()->first();
                $model = new chat_message();
                $existing = $model->findByClientMessageId($chatId, $message['clientMessageId']);

                if ($existing !== null) {
                    return ['message' => $this->normalizeMessage($existing), 'inserted' => false];
                }

                $parent = $model->findForChat($chatId, $parentMessageId);

                if ($parent === null) {
                    throw new RuntimeException('Message not found.');
                }

                $message['text'] = ChatReply::compose($this->normalizeMessage($parent), $message['text']);

                if (strlen($message['text']) > 16000) {
                    throw new RuntimeException('Message is too large.');
                }

                $row = $this->insertMessage($model, $chatId, $message);
                (new chat_message())->pruneChat($chatId, 1000);

                return ['message' => $this->normalizeMessage($row), 'inserted' => true];
            });
        }
        catch (Throwable $error) {
            $row = (new chat_message())->findByClientMessageId($chatId, $message['clientMessageId']);

            if ($row === null) {
                throw $error;
            }

            return ['message' => $this->normalizeMessage($row), 'inserted' => false];
        }
    }

    public function editMessage(int $chatId, int $messageId, string $text): array
    {
        $model = new chat_message();
        $existing = $model->findForChat($chatId, $messageId);

        if ($existing === null) {
            throw new RuntimeException('Message not found.');
        }

        $text = ChatReply::replaceOwnText((string) $existing->message_text, $text);

        if (strlen($text) > 16000) {
            throw new RuntimeException('Message is too large.');
        }

        $row = $model->updateMessageText($chatId, $messageId, $text);

        if ($row === null) {
            throw new RuntimeException('Message not found.');
        }

        return $this->normalizeMessage($row);
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        if ((new chat())->findByDiscussionMessageId($messageId) !== null) {
            throw new RuntimeException('A message with a discussion cannot be deleted.');
        }
        $room = Dal::table('chat')->where('id', '=', $chatId)->first();
        if ($room !== null && $room->discussion_of_message_id !== null) {
            $seedId = Dal::table('chat_message')->where('chat_id', '=', $chatId)->min('id');
            if ((int) $seedId === $messageId) throw new RuntimeException('A discussion seed cannot be deleted.');
        }
        return (new chat_message())->deleteMessage($chatId, $messageId);
    }

    public function toggleMessagePin(int $chatId, int $messageId): ?array
    {
        return Dal::connection()->transaction(function () use ($chatId, $messageId): ?array {
            $message = Dal::table('chat_message')->where('chat_id', '=', $chatId)->where('id', '=', $messageId)->lockForUpdate()->first();
            if ($message === null) return null;
            $pin = ! (bool) $message->is_pinned;
            Dal::table('chat_message')->where('chat_id', '=', $chatId)->where('is_pinned', '=', 1)->update(['is_pinned' => 0]);
            if ($pin) Dal::table('chat_message')->where('id', '=', $messageId)->update(['is_pinned' => 1]);
            return $pin ? $this->pinnedMessage($chatId) : null;
        });
    }

    public function pinnedMessage(int $chatId): ?array
    {
        $row = (new chat_message())->pinnedForChat($chatId);
        return $row === null ? null : $this->normalizeMessage($row);
    }

    public function createDiscussion(int $chatId, int $messageId): array
    {
        return Dal::connection()->transaction(function () use ($chatId, $messageId): array {
            $parent = Dal::table('chat')->where('id', '=', $chatId)->lockForUpdate()->first();
            $source = (new chat_message())->findForChat($chatId, $messageId);
            if ($parent === null || $source === null) throw new RuntimeException('Message not found.');

            $model = new chat();
            $child = $model->findByDiscussionMessageId($messageId);
            $created = false;
            if ($child === null) {
                $child = $model->insertDiscussion(
                    (string) $parent->origin,
                    'discussion_' . $messageId,
                    ChatDiscussion::title($messageId, (string) $source->message_text),
                    $messageId,
                );
                $this->insertMessage(new chat_message(), (int) $child->id, [
                    'senderType' => (string) $source->sender_type,
                    'senderId' => (string) $source->identity_sender_id,
                    'senderName' => (string) $source->identity_sender_name,
                    'senderEmblem' => (string) $source->identity_sender_emblem,
                    'text' => (string) $source->message_text,
                    'createdAt' => (int) $source->created_at,
                    'clientMessageId' => 'discussion-seed-' . $messageId,
                ]);
                $created = true;
            }
            $seed = Dal::table('chat_message')->where('chat_id', '=', $child->id)->orderBy('id')->first();
            return [
                'chat' => $this->normalizeChat($child),
                'seedMessage' => $this->normalizeMessage($seed),
                'responseCount' => max(0, Dal::table('chat_message')->where('chat_id', '=', $child->id)->count() - 1),
                'created' => $created,
            ];
        });
    }

    public function discussionForMessage(int $chatId, int $messageId): ?array
    {
        if ((new chat_message())->findForChat($chatId, $messageId) === null) return null;
        $child = (new chat())->findByDiscussionMessageId($messageId);
        if ($child === null) return null;
        return [
            'channel' => (string) $child->channel_name_pseudo_id,
            'responseCount' => max(0, Dal::table('chat_message')->where('chat_id', '=', $child->id)->count() - 1),
        ];
    }

    public function discussionParent(int $chatId): ?array
    {
        $child = Dal::table('chat')->where('id', '=', $chatId)->first();
        if ($child === null || $child->discussion_of_message_id === null) return null;
        $parent = Dal::table('chat_message')->join('chat', 'chat.id', '=', 'chat_message.chat_id')
            ->where('chat_message.id', '=', $child->discussion_of_message_id)
            ->first(['chat.origin', 'chat.channel_name_pseudo_id']);
        if ($parent === null) return null;
        return [
            'origin' => (string) $parent->origin,
            'channel' => (string) $parent->channel_name_pseudo_id,
            'sourceMessageId' => (int) $child->discussion_of_message_id,
            'responseCount' => max(0, Dal::table('chat_message')->where('chat_id', '=', $chatId)->count() - 1),
        ];
    }

    public function deleteChat(string $origin, string $channel): array
    {
        return Dal::connection()->transaction(function () use ($origin, $channel): array {
            $root = Dal::table('chat')->where('origin', '=', $origin)->where('channel_name_pseudo_id', '=', $channel)->lockForUpdate()->first();
            if ($root === null) throw new RuntimeException('Chat not found.');
            $restored = null;
            if ($root->discussion_of_message_id !== null) {
                $parent = Dal::table('chat_message')->join('chat', 'chat.id', '=', 'chat_message.chat_id')
                    ->where('chat_message.id', '=', $root->discussion_of_message_id)
                    ->first(['chat.origin', 'chat.channel_name_pseudo_id']);
                if ($parent !== null) $restored = ['origin'=>(string)$parent->origin,'channel'=>(string)$parent->channel_name_pseudo_id,'sourceMessageId'=>(int)$root->discussion_of_message_id];
            }
            $channels = [];
            $remove = function (stdClass $room) use (&$remove, &$channels): void {
                $messageIds = Dal::table('chat_message')->where('chat_id', '=', $room->id)->pluck('id')->all();
                if ($messageIds !== []) {
                    $children = Dal::table('chat')->whereIn('discussion_of_message_id', $messageIds)->lockForUpdate()->get()->all();
                    foreach ($children as $child) $remove($child);
                }
                Dal::table('chat_message')->where('chat_id', '=', $room->id)->delete();
                Dal::table('chat')->where('id', '=', $room->id)->delete();
                $channels[] = (string) $room->channel_name_pseudo_id;
            };
            $remove($root);
            return ['channels' => $channels, 'restored' => $restored];
        });
    }

    public function renameChat(int $chatId, string $name, int $updatedAt): array
    {
        $row = (new chat())->rename($chatId, $name, $updatedAt);

        if (! $row) {
            throw new RuntimeException('Chat not found.');
        }

        return $this->normalizeChat($row);
    }

    public function renameGuest(int $chatId, string $guestId, string $name): int
    {
        return Dal::connection()->transaction(function () use ($guestId, $name): int {
            $identity = Dal::table('chat_user')->where('user_type', '=', 'guest')->where('user_id', '=', $guestId)->first();
            if ($identity === null) return 0;
            $messageCount = Dal::table('chat_message')->where('chat_user_id', '=', $identity->id)->count();
            (new chat_user())->rename('guest', $guestId, $name, (int) floor(microtime(true) * 1000));
            return $messageCount;
        });
    }

    public function setAnonymousAllowed(int $chatId, bool $allowed, int $updatedAt): array
    {
        $row = (new chat())->setAnonymousAllowed($chatId, $allowed, $updatedAt);

        if (! $row) {
            throw new RuntimeException('Chat not found.');
        }

        return $this->normalizeChat($row);
    }

    public function setConfirmationRequired(int $chatId, bool $required, int $updatedAt): array
    {
        $row = (new chat())->setConfirmationRequired($chatId, $required, $updatedAt);

        if (! $row) {
            throw new RuntimeException('Chat not found.');
        }

        return $this->normalizeChat($row);
    }

    public function deleteParticipantMessages(int $chatId, string $senderType, string $senderId): int
    {
        return (new chat_message())->deleteParticipant($chatId, $senderType, $senderId);
    }

    private function normalizeChat(stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'origin' => (string) $row->origin,
            'channel' => (string) $row->channel_name_pseudo_id,
            'name' => (string) $row->chat_name_human,
            'isAnionAllowed' => (bool) $row->is_anion_allowed,
            'isConfirmationRequired' => (bool) $row->is_confirmation_required,
            'discussionOfMessageId' => $row->discussion_of_message_id === null ? null : (int) $row->discussion_of_message_id,
            'createdAt' => (int) $row->created_at,
            'updatedAt' => (int) $row->updated_at,
        ];
    }

    private function userMessageCount(int $chatId): int
    {
        return count(Dal::table('chat_message')->where('chat_id', '=', $chatId)->where('sender_type', '=', 'user')->limit(5001)->pluck('id'));
    }

    private function insertMessage(chat_message $model, int $chatId, array $message): stdClass
    {
        $identity = $this->upsertChatUser($message);
        return $model->insertMessage($chatId, $message + ['chatUserId' => (int) $identity->id]);
    }

    private function upsertChatUser(array $message): stdClass
    {
        return (new chat_user())->upsertIdentity(
            ChatUserIdentity::type($message['senderType'], (string) $message['senderId']),
            (string) $message['senderId'],
            (string) $message['senderName'],
            (string) $message['senderEmblem'],
            (int) $message['createdAt'],
        );
    }

    private function normalizeMessage(stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'senderType' => (string) $row->sender_type,
            'senderId' => (string) $row->identity_sender_id,
            'senderName' => (string) $row->identity_sender_name,
            'senderEmblem' => (string) $row->identity_sender_emblem,
            'text' => (string) $row->message_text,
            'createdAt' => (int) $row->created_at,
            'clientMessageId' => (string) $row->client_message_id,
            'isPinned' => (bool) ($row->is_pinned ?? false),
        ];
    }
}
