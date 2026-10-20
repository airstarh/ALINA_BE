<?php

declare(strict_types=1);

namespace alina\Services\Chat;

final class ChatMentions
{
    public const MAX_PER_MESSAGE = 22;
    public const DIRECTORY_LIMIT = 1000;
    public const SEARCH_LIMIT = 5;
    public const SEARCH_MINIMUM = 3;

    public static function requested(mixed $value): ?array
    {
        if ($value === null) return [];
        if (! is_array($value)) return null;
        $result = [];
        foreach ($value as $target) {
            if (! is_array($target) || ! in_array($target['senderType'] ?? null, ['user', 'guest'], true) || ! is_string($target['senderId'] ?? null)) return null;
            $key = $target['senderType'] . ':' . $target['senderId'];
            $result[$key] = ['senderType' => $target['senderType'], 'senderId' => $target['senderId']];
            if (count($result) > self::MAX_PER_MESSAGE) return null;
        }
        return array_values($result);
    }
}
