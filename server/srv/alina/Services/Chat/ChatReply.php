<?php

declare(strict_types=1);

namespace alina\Services\Chat;

final class ChatReply
{
    private const PREFIX_PATTERN = '~\A(?<prefix><div class="reply-quote" data-timestamp="[0-9]+"><div class="reply-quote-author">[\s\S]*?</div><div class="reply-quote-text">[\s\S]*?</div></div>\n)(?<body>[\s\S]*)\z~';

    public static function hasCanonicalPrefix(string $text): bool
    {
        return preg_match(self::PREFIX_PATTERN, $text) === 1;
    }

    public static function ownText(string $text): string
    {
        return self::parts($text)['body'] ?? $text;
    }

    public static function replaceOwnText(string $existingText, string $newOwnText): string
    {
        $parts = self::parts($existingText);

        return $parts === null ? $newOwnText : $parts['prefix'] . $newOwnText;
    }

    public static function compose(array $parent, string $replyText): string
    {
        $author = self::escape((string) ($parent['senderName'] ?? ''));
        $excerpt = self::escape(mb_substr(self::ownText((string) ($parent['text'] ?? '')), 0, 150));
        $timestamp = max(0, (int) ($parent['createdAt'] ?? 0));

        return '<div class="reply-quote" data-timestamp="' . $timestamp . '">' .
            '<div class="reply-quote-author">' . $author . '</div>' .
            '<div class="reply-quote-text">' . $excerpt . '</div></div>' . "\n" .
            $replyText;
    }

    /** @return null|array{prefix: string, body: string} */
    private static function parts(string $text): ?array
    {
        if (preg_match(self::PREFIX_PATTERN, $text, $matches) !== 1) {
            return null;
        }

        return ['prefix' => $matches['prefix'], 'body' => $matches['body']];
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
