<?php

declare(strict_types=1);

namespace alina\Services\Chat;

interface ChatStorage
{
    public function openChat(string $origin, string $channel): array;

    public function listRooms(string $origin, string $senderType, string $senderId, array $pagination): array;

    public function history(int $chatId, int $limit = 50): array;

    public function hasAnyMessage(int $chatId): bool;

    public function hasParticipantMessage(int $chatId, string $senderType, string $senderId): bool;

    /**
     * @return array{message: array, inserted: bool}
     */
    public function appendMessage(int $chatId, array $message): array;

    public function renameChat(int $chatId, string $name, int $updatedAt): array;

    public function setAnonymousAllowed(int $chatId, bool $allowed, int $updatedAt): array;

    public function setConfirmationRequired(int $chatId, bool $required, int $updatedAt): array;

    public function deleteParticipantMessages(int $chatId, string $senderType, string $senderId): int;
}
