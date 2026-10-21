<?php

declare(strict_types=1);

use Cam\TalkToken;

function loadTalkTokenClass(): void
{
    $path = dirname(__DIR__) . '/php/TalkToken.php';
    if (!is_file($path)) {
        throw new RuntimeException('Cam\\TalkToken does not exist');
    }
    require_once $path;
}

test('talk_token_round_trips_camera_and_rejects_tampering_or_expiry', function (): void {
    loadTalkTokenClass();
    $tokens = new TalkToken(str_repeat('s', 32), 30);
    $issued = $tokens->issue('balcon', 1_000);

    assertSameValue(1_030, $issued['expiresAt']);
    assertSameValue('balcon', $tokens->verify($issued['token'], 1_029));
    assertThrows(
        static fn () => $tokens->verify($issued['token'] . 'changed', 1_029),
        InvalidArgumentException::class
    );
    assertThrows(
        static fn () => $tokens->verify($issued['token'], 1_030),
        InvalidArgumentException::class
    );
});

test('talk_token_fails_closed_without_a_server_secret', function (): void {
    loadTalkTokenClass();
    $previous = getenv('ALINA_CAM_TALK_SECRET');
    putenv('ALINA_CAM_TALK_SECRET');
    try {
        assertThrows(static fn () => TalkToken::fromEnvironment(), RuntimeException::class);
    } finally {
        if ($previous === false) {
            putenv('ALINA_CAM_TALK_SECRET');
        } else {
            putenv('ALINA_CAM_TALK_SECRET=' . $previous);
        }
    }
});
