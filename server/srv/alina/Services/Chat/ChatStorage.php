<?php

declare(strict_types=1);

namespace alina\Services\Chat;

interface ChatStorage
{
    public function openChat(string $origin, string $channel): array;

    public function listRooms(string $origin, string $senderType, string $senderId, array $pagination): array;

    public function mentionCandidates(string $origin, string $senderType, string $senderId, string $query, int $limit): array;

    public function participantSnapshots(int $chatId): array;

    public function history(int $chatId, int $limit = 50): array;

    public function hasAnyMessage(int $chatId): bool;

    public function hasParticipantMessage(int $chatId, string $senderType, string $senderId): bool;

    /**
     * @return array{message: array, inserted: bool}
     */
    public function appendMessage(int $chatId, array $message): array;

    public function appendMessageGroup(int $chatId, array $message, array $targets): array;

    /**
     * @return array{message: array, inserted: bool}
     */
    public function appendReply(int $chatId, int $parentMessageId, array $message): array;

    public function editMessage(int $chatId, int $messageId, string $text): array;

    public function deleteMessage(int $chatId, int $messageId): bool;

    public function createDiscussion(int $chatId, int $messageId): array;

    public function discussionForMessage(int $chatId, int $messageId): ?array;

    public function discussionParent(int $chatId): ?array;

    public function renameChat(int $chatId, string $name, int $updatedAt): array;

    public function renameGuest(int $chatId, string $guestId, string $name): int;

    public function setAnonymousAllowed(int $chatId, bool $allowed, int $updatedAt): array;

    public function setConfirmationRequired(int $chatId, bool $required, int $updatedAt): array;

    public function deleteParticipantMessages(int $chatId, string $senderType, string $senderId): int;
}
