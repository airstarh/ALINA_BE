<?php

declare(strict_types=1);

namespace alina\Services\Chat;

final class ChatUserIdentity
{
    public static function type(string $senderType, string $senderId): string
    {
        if ($senderType !== 'user_service') return $senderType;
        return ctype_digit($senderId) ? 'user' : 'guest';
    }
}
