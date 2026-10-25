<?php

declare(strict_types=1);

namespace alina\Services\Chat;

interface ChatStorage
{
    public function openChat(string $origin, string $channel, ?string $initialName = null, bool $private = false): array;

    public function listRooms(string $origin, string $senderType, string $senderId, array $pagination): array;

    public function searchRooms(string $origin, string $senderId, string $query, int $limit): array;

    public function toggleChatPin(string $origin, string $channel, string $userId, string $userName, string $userEmblem, int $createdAt): bool;

    public function mentionCandidates(string $origin, string $senderType, string $senderId, string $query, int $limit): array;

    public function participantSnapshots(int $chatId): array;

    public function searchUsers(string $query, int $limit): array;

    public function history(int $chatId, int $limit = 200, ?int $beforeMessageId = null): array;

    public function hasAnyMessage(int $chatId): bool;

    public function hasParticipantMessage(int $chatId, string $senderType, string $senderId): bool;

    public function toggleReaction(int $chatId, int $messageId, string $senderType, string $senderId, int $reaction, int $updatedAt): void;

    public function reactionSummary(int $chatId, int $messageId, string $senderType, string $senderId): array;

    public function reactionSummaries(int $chatId, array $messageIds, string $senderType, string $senderId): array;

    public function reactionPeople(int $chatId, int $messageId): array;

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

    public function toggleMessagePin(int $chatId, int $messageId): ?array;

    public function pinnedMessage(int $chatId): ?array;

    public function createDiscussion(int $chatId, int $messageId): array;

    public function discussionForMessage(int $chatId, int $messageId): ?array;

    public function discussionParent(int $chatId): ?array;

    public function deleteChat(string $origin, string $channel): array;

    public function renameChat(int $chatId, string $name, int $updatedAt): array;

    public function renameGuest(int $chatId, string $guestId, string $name): int;

    public function setAnonymousAllowed(int $chatId, bool $allowed, int $updatedAt): array;

    public function setConfirmationRequired(int $chatId, bool $required, int $updatedAt): array;

    public function deleteParticipantMessages(int $chatId, string $senderType, string $senderId): int;
}
