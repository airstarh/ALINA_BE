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
            'chat_user_id' => [],
            'sender_type' => [],
            'message_text' => [],
            'created_at' => ['default' => self::nowMilliseconds()],
            'client_message_id' => [],
        ];
    }

    public function uniqueKeys(): array
    {
        return [
            ['chat_id', 'client_message_id', 'sender_type', 'chat_user_id'],
        ];
    }

    public function latestForChat(int $chatId, int $limit, ?int $beforeMessageId = null): array
    {
        $query = $this->withChatUser()->where('chat_message.chat_id', '=', $chatId);
        if ($beforeMessageId !== null) $query->where('chat_message.id', '<', $beforeMessageId);

        return $query
            ->orderBy('chat_message.id', 'DESC')
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

        return $this->q('chat_message')
            ->join('chat_user', 'chat_user.id', '=', 'chat_message.chat_user_id')
            ->where('chat_message.chat_id', '=', $chatId)
            ->whereIn('chat_message.sender_type', $types)
            ->where('chat_user.user_type', '=', $senderType)
            ->where('chat_user.user_id', '=', $senderId)
            ->exists();
    }

    public function findByClientMessageId(int $chatId, string $clientMessageId): ?stdClass
    {
        return $this->withChatUser()
            ->where('chat_message.chat_id', '=', $chatId)
            ->where('chat_message.client_message_id', '=', $clientMessageId)
            ->where('chat_message.sender_type', '!=', 'user_service')
            ->first();
    }

    public function findGroup(int $chatId, string $clientMessageId): array
    {
        return $this->withChatUser()->where('chat_message.chat_id', '=', $chatId)->where('chat_message.client_message_id', '=', $clientMessageId)->orderBy('chat_message.id')->get()->all();
    }

    public function findForChat(int $chatId, int $messageId): ?stdClass
    {
        return $this->withChatUser()
            ->where('chat_message.chat_id', '=', $chatId)
            ->where('chat_message.id', '=', $messageId)
            ->first();
    }

    public function insertMessage(int $chatId, array $message): stdClass
    {
        $id = $this->q(-1)->insertGetId([
            'chat_id' => $chatId,
            'chat_user_id' => $message['chatUserId'],
            'sender_type' => $message['senderType'],
            'message_text' => $message['text'],
            'created_at' => $message['createdAt'],
            'client_message_id' => $message['clientMessageId'],
        ]);

        return $this->withChatUser()->where('chat_message.id', '=', $id)->first();
    }

    public function updateMessageText(int $chatId, int $messageId, string $text): ?stdClass
    {
        $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->where('id', '=', $messageId)
            ->update(['message_text' => $text]);

        return $this->withChatUser()
            ->where('chat_message.chat_id', '=', $chatId)
            ->where('chat_message.id', '=', $messageId)
            ->first();
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        return $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->where('id', '=', $messageId)
            ->where('is_pinned', '=', 0)
            ->delete() === 1;
    }

    public function pinnedForChat(int $chatId): ?stdClass
    {
        return $this->withChatUser()->where('chat_message.chat_id', '=', $chatId)->where('chat_message.is_pinned', '=', 1)->first();
    }

    private function withChatUser()
    {
        return $this->q('chat_message')
            ->leftJoin('chat_user', 'chat_user.id', '=', 'chat_message.chat_user_id')
            ->select('chat_message.*')
            ->addSelect('chat_user.user_id AS identity_sender_id')
            ->addSelect('chat_user.user_name AS identity_sender_name')
            ->addSelect('chat_user.user_emblem AS identity_sender_emblem');
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
                ->where('is_pinned', '=', 0)->where('id', '<', $oldestResponseId)->delete();
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
            ->where('is_pinned', '=', 0)
            ->where('id', '<', $oldestRetainedId)
            ->delete();
    }

    public function deleteParticipant(int $chatId, string $senderType, string $senderId): int
    {
        $types = $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'];
        $chatUserId = Dal::table('chat_user')->where('user_type', '=', $senderType)->where('user_id', '=', $senderId)->value('id');
        if ($chatUserId === null) return 0;
        return $this->q(-1)
            ->where('chat_id', '=', $chatId)
            ->whereIn('sender_type', $types)
            ->where('chat_user_id', '=', $chatUserId)
            ->where('is_pinned', '=', 0)
            ->delete();
    }

    public static function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
