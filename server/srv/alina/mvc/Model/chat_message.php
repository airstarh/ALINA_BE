<?php

declare(strict_types=1);

namespace alina\mvc\Model;

use Illuminate\Database\Capsule\Manager as Dal;
use stdClass;

class chat_message extends _BaseAlinaModel
{
    public $table = 'chat_message';

    public function fields(): array
    {
        return [
            'id' => [],
            'chat_id' => [],
            'sender_type' => [],
            'sender_id' => [],
            'sender_name' => [],
            'sender_emblem' => [],
            'message_text' => [],
            'created_at' => ['default' => self::nowMilliseconds()],
            'client_message_id' => [],
        ];
    }

    public function uniqueKeys(): array
    {
        return [
            ['chat_id', 'client_message_id', 'sender_type', 'sender_id'],
        ];
    }

    public function latestForChat(int $chatId, int $limit): array
    {
        return $this->q()
            ->where('chat_id', '=', $chatId)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->all();
    }

    public function hasAnyForChat(int $chatId): bool
    {
        return $this->q()->where('chat_id', '=', $chatId)->exists();
    }

    public function hasParticipantMessage(int $chatId, string $senderType, string $senderId): bool
    {
        if ($senderType === 'bot') {
            return false;
        }

        $types = $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'];

        return $this->q()
            ->where('chat_id', '=', $chatId)
            ->whereIn('sender_type', $types)
            ->where('sender_id', '=', $senderId)
            ->exists();
    }

    public function findByClientMessageId(int $chatId, string $clientMessageId): ?stdClass
    {
        return $this->q()
            ->where('chat_id', '=', $chatId)
            ->where('client_message_id', '=', $clientMessageId)
            ->where('sender_type', '!=', 'user_service')
            ->first();
    }

    public function findGroup(int $chatId, string $clientMessageId): array
    {
        return $this->q()->where('chat_id', '=', $chatId)->where('client_message_id', '=', $clientMessageId)->orderBy('id')->get()->all();
    }

    public function findForChat(int $chatId, int $messageId): ?stdClass
    {
        return $this->q()
            ->where('chat_id', '=', $chatId)
            ->where('id', '=', $messageId)
            ->first();
    }

    public function insertMessage(int $chatId, array $message): stdClass
    {
        $id = $this->q(-1)->insertGetId([
            'chat_id' => $chatId,
            'sender_type' => $message['senderType'],
            'sender_id' => $message['senderId'],
            'sender_name' => $message['senderName'],
            'sender_emblem' => $message['senderEmblem'],
            'message_text' => $message['text'],
            'created_at' => $message['createdAt'],
            'client_message_id' => $message['clientMessageId'],
        ]);

        return $this->q()->where('id', '=', $id)->first();
    }

    public function updateMessageText(int $chatId, int $messageId, string $text): ?stdClass
    {
        $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->where('id', '=', $messageId)
            ->update(['message_text' => $text]);

        return $this->q()
            ->where('chat_id', '=', $chatId)
            ->where('id', '=', $messageId)
            ->first();
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        return $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->where('id', '=', $messageId)
            ->delete() === 1;
    }

    public function renameGuest(int $chatId, string $guestId, string $name): int
    {
        return $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->whereIn('sender_type', ['guest', 'user_service'])
            ->where('sender_id', '=', $guestId)
            ->update(['sender_name' => $name]);
    }

    public function pruneChat(int $chatId, int $maximum = 1000): int
    {
        $room = Dal::table('chat')->where('id', '=', $chatId)->first();
        if ($room !== null && $room->discussion_of_message_id !== null) {
            $seedId = $this->q()->where('chat_id', '=', $chatId)->orderBy('id')->value('id');
            $oldestResponseId = $this->q()->where('chat_id', '=', $chatId)->where('id', '!=', $seedId)
                ->orderBy('id', 'DESC')->offset($maximum - 2)->limit(1)->value('id');
            if ($oldestResponseId === null) return 0;
            return $this->q(-1)->where('chat_id', '=', $chatId)->where('id', '!=', $seedId)
                ->where('id', '<', $oldestResponseId)->delete();
        }

        $oldestRetainedId = $this->q()
            ->where('chat_id', '=', $chatId)
            ->orderBy('id', 'DESC')
            ->offset($maximum - 1)
            ->limit(1)
            ->value('id');

        if ($oldestRetainedId === null) {
            return 0;
        }

        return $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->where('id', '<', $oldestRetainedId)
            ->delete();
    }

    public function deleteParticipant(int $chatId, string $senderType, string $senderId): int
    {
        $types = $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'];
        return $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->whereIn('sender_type', $types)
            ->where('sender_id', '=', $senderId)
            ->delete();
    }

    public static function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
