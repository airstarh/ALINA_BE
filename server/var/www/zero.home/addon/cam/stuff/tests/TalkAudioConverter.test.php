<?php

declare(strict_types=1);

use Cam\TalkAudioConverter;
use Cam\TalkSessionRegistry;

function loadTalkServiceClasses(): void
{
    $directory = dirname(__DIR__) . '/php';
    foreach (['TalkAudioConverter.php', 'TalkSessionRegistry.php'] as $file) {
        $path = $directory . '/' . $file;
        if (!is_file($path)) throw new RuntimeException("{$file} does not exist");
        require_once $path;
    }
}

test('talk converter describes persistent 8k mono PCM to G711 A-law conversion', function (): void {
    loadTalkServiceClasses();
    $converter = new TalkAudioConverter('/opt/ffmpeg', '/tmp/cam-talk-test.log');
    $command = $converter->command();

    assertSameValue('/opt/ffmpeg', $command[0]);
    assertTrueValue(in_array('s16le', $command, true));
    assertTrueValue(in_array('alaw', $command, true));
    assertTrueValue(in_array('8000', $command, true));
    assertTrueValue(in_array('pipe:0', $command, true));
    assertTrueValue(in_array('pipe:1', $command, true));
});

test('talk converter emits only complete 320-byte camera frames', function (): void {
    loadTalkServiceClasses();
    $converter = new TalkAudioConverter('/opt/ffmpeg', '/tmp/cam-talk-test.log');
    assertSameValue([], $converter->consumeAlaw(str_repeat("\x55", 319)));
    $frames = $converter->consumeAlaw(str_repeat("\x66", 322));
    assertSameValue(2, count($frames));
    assertSameValue(320, strlen($frames[0]));
    assertSameValue(320, strlen($frames[1]));
});

test('talk registry permits one owner per camera and releases it', function (): void {
    loadTalkServiceClasses();
    $registry = new TalkSessionRegistry();
    $first = new stdClass();
    $second = new stdClass();

    assertSameValue(true, $registry->acquire('balcon', $first));
    assertSameValue(false, $registry->acquire('balcon', $second));
    $registry->release('balcon', $second);
    assertSameValue(false, $registry->acquire('balcon', $second));
    $registry->release('balcon', $first);
    assertSameValue(true, $registry->acquire('balcon', $second));
});
