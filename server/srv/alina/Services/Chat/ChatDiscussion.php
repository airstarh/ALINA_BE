<?php

declare(strict_types=1);

namespace alina\Services\Chat;

final class ChatDiscussion
{
    private const TITLE_PREFIX = 'DISCUSS OF: ';

    public static function title(int $messageId, string $text): string
    {
        if (
            ChatReply::hasCanonicalPrefix($text)
            || str_starts_with($text, 'VOICE:')
            || preg_match('~https?://\S+\.(?:jpe?g|png|gif|webp|svg|avif|mp3|wav|ogg|oga|m4a|aac|flac|opus|mp4|m4v|webm|ogv|mov|avi|mkv)(?:[?#]\S*)?~iu', $text)
        ) {
            return self::TITLE_PREFIX . 'message ' . $messageId;
        }

        $plain = trim(strip_tags($text));
        $title = mb_substr($plain, 0, 100);
        if (mb_strlen($plain) > 100 && preg_match('/^(.{1,100})(?:\s|$)/us', $plain, $match)) {
            $title = rtrim($match[1]);
        }

        return self::TITLE_PREFIX . ($title !== '' ? $title : 'message ' . $messageId);
    }
}
