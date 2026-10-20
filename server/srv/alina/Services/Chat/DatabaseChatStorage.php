<?php

declare(strict_types=1);

namespace alina\Services\Chat;

use alina\mvc\Model\chat;
use alina\mvc\Model\chat_message;
use Illuminate\Database\Capsule\Manager as Dal;
use RuntimeException;
use stdClass;
use Throwable;

final class DatabaseChatStorage implements ChatStorage
{
    public function openChat(string $origin, string $channel): array
    {
        $row = (new chat())->findByOriginAndChannel($origin, $channel);

        if ($row === null) {
            try {
                $row = (new chat())->insertForChannel($origin, $channel);
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

    public function mentionCandidates(string $origin, string $senderType, string $senderId, string $query, int $limit): array
    {
        $pattern = '%' . addcslashes($query, '%_\\') . '%';
        $rows = Dal::table('chat_message')
            ->join('chat', 'chat.id', '=', 'chat_message.chat_id')
            ->where('chat.origin', '=', $origin)
            ->whereIn('chat_message.sender_type', ['user', 'guest', 'user_service'])
            ->when($query !== '', function ($outer) use ($origin, $pattern): void {
                $outer->whereIn('chat_message.sender_id', function ($matching) use ($origin, $pattern): void {
                    $matching->select('mention_search.sender_id')->from('chat_message AS mention_search')
                        ->join('chat AS mention_chat', 'mention_chat.id', '=', 'mention_search.chat_id')
                        ->where('mention_chat.origin', '=', $origin)
                        ->whereIn('mention_search.sender_type', ['user', 'guest', 'user_service'])
                        ->whereRaw("mention_search.sender_name LIKE ? ESCAPE '\\\\'", [$pattern]);
                });
            })
            ->orderBy('chat_message.created_at', 'DESC')
            ->orderBy('chat_message.id', 'DESC')
            ->get(['chat_message.sender_type', 'chat_message.sender_id', 'chat_message.sender_name', 'chat_message.sender_emblem', 'chat_message.created_at']);
        $result = [];
        foreach ($rows as $row) {
            $id = (string) $row->sender_id;
            if ($id === $senderId) continue;
            $type = $row->sender_type === 'user_service' ? (ctype_digit($id) ? 'user' : 'guest') : (string) $row->sender_type;
            if (! isset($result[$id])) {
                $result[$id] = ['senderType' => $type, 'senderId' => $id, 'senderName' => (string) $row->sender_name, 'senderEmblem' => (string) $row->sender_emblem, 'latestActivityAt' => (int) $row->created_at, '_genuine' => $row->sender_type !== 'user_service'];
            }
            elseif (! $result[$id]['_genuine'] && $row->sender_type !== 'user_service') {
                $result[$id]['senderType'] = $type;
                $result[$id]['senderName'] = (string) $row->sender_name;
                $result[$id]['senderEmblem'] = (string) $row->sender_emblem;
                $result[$id]['_genuine'] = true;
            }
        }
        $result = array_values(array_filter($result, fn ($candidate) => $query === '' || mb_stripos($candidate['senderName'], $query) !== false));
        foreach ($result as &$candidate) unset($candidate['_genuine']);
        return array_slice($result, 0, $limit);
    }

    public function participantSnapshots(int $chatId): array
    {
        $result = [];
        foreach (array_reverse((new chat_message())->latestForChat($chatId, 1000)) as $row) {
            if (! in_array($row->sender_type, ['user', 'guest', 'user_service'], true)) continue;
            $senderId = (string) $row->sender_id;
            $senderType = $row->sender_type === 'user_service'
                ? (ctype_digit($senderId) ? 'user' : 'guest')
                : (string) $row->sender_type;
            $key = $senderType . ':' . $senderId;
            $result[$key] ??= [
                'senderType' => $senderType,
                'senderId' => $senderId,
                'senderName' => (string) $row->sender_name,
                'senderEmblem' => (string) $row->sender_emblem,
            ];
        }
        return array_values($result);
    }

    public function history(int $chatId, int $limit = 50): array
    {
        return array_map(
            fn (stdClass $row): array => $this->normalizeMessage($row),
            (new chat_message())->latestForChat($chatId, $limit),
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

                $row = $model->insertMessage($chatId, $message);
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
            foreach ($targets as $target) $stored[] = $this->normalizeMessage($model->insertMessage($chatId, $target));
            $stored[] = $this->normalizeMessage($model->insertMessage($chatId, $message));
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

                $row = $model->insertMessage($chatId, $message);
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
                (new chat_message())->insertMessage((int) $child->id, [
                    'senderType' => (string) $source->sender_type,
                    'senderId' => (string) $source->sender_id,
                    'senderName' => (string) $source->sender_name,
                    'senderEmblem' => (string) $source->sender_emblem,
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
        return (new chat_message())->renameGuest($chatId, $guestId, $name);
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

    private function normalizeMessage(stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'senderType' => (string) $row->sender_type,
            'senderId' => (string) $row->sender_id,
            'senderName' => (string) $row->sender_name,
            'senderEmblem' => (string) $row->sender_emblem,
            'text' => (string) $row->message_text,
            'createdAt' => (int) $row->created_at,
            'clientMessageId' => (string) $row->client_message_id,
        ];
    }
}
