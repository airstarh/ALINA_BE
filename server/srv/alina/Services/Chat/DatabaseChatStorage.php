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

    public function renameChat(int $chatId, string $name, int $updatedAt): array
    {
        $row = (new chat())->rename($chatId, $name, $updatedAt);

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
            'text' => (string) $row->message_text,
            'createdAt' => (int) $row->created_at,
            'clientMessageId' => (string) $row->client_message_id,
        ];
    }
}
