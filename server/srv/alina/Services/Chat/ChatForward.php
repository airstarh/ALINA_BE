<?php

declare(strict_types=1);

namespace alina\Services\Chat;

final class ChatForward
{
    private const PATTERN = '~\A<div class="forward-quote" data-channel="([A-Za-z0-9_-]{1,128})" data-message-id="([1-9][0-9]*)" data-timestamp="([0-9]+)"><div class="forward-quote-author">([\s\S]*?)</div><div class="forward-quote-text">([\s\S]*?)</div></div>\z~';

    public static function hasCanonicalPrefix(string $text): bool
    {
        return preg_match(self::PATTERN, $text) === 1;
    }

    public static function compose(array $source): string
    {
        $text = (string) ($source['text'] ?? '');
        $forward = self::parts($text);
        if ($forward !== null) $text = $forward['text'];
        else $text = ChatReply::ownText($text);

        return '<div class="forward-quote" data-channel="' . self::escape((string) ($source['channel'] ?? '')) .
            '" data-message-id="' . max(1, (int) ($source['id'] ?? 0)) .
            '" data-timestamp="' . max(0, (int) ($source['createdAt'] ?? 0)) . '">' .
            '<div class="forward-quote-author">' . self::escape((string) ($source['senderName'] ?? '')) . '</div>' .
            '<div class="forward-quote-text">' . self::escape(mb_substr($text, 0, 150)) . '</div></div>';
    }

    public static function parts(string $text): ?array
    {
        if (preg_match(self::PATTERN, $text, $match) !== 1) return null;
        return [
            'channel' => $match[1], 'messageId' => (int) $match[2], 'timestamp' => (int) $match[3],
            'author' => html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'text' => html_entity_decode($match[5], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
