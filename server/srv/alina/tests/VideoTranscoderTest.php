<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/FileJob/ProcessResult.php';
require_once __DIR__ . '/../Services/FileJob/VideoTranscoder.php';

use alina\Services\FileJob\VideoTranscoder;

function videoExpectSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
}

$transcoder = new VideoTranscoder('/usr/bin/ffmpeg', '/usr/bin/ffprobe');
$probe = $transcoder->parseProbe(json_encode([
    'streams' => [['codec_type'=>'video','duration'=>'12.5'], ['codec_type'=>'audio']],
    'format' => ['duration'=>'12.5'],
]));
videoExpectSame(12.5, $probe['duration'], 'video duration must be parsed');
videoExpectSame(true, $probe['hasAudio'], 'audio presence must be detected');

$rejected = false;
try { $transcoder->parseProbe(json_encode(['streams'=>[['codec_type'=>'audio']], 'format'=>['duration'=>'2']])); }
catch (InvalidArgumentException) { $rejected = true; }
videoExpectSame(true, $rejected, 'non-video input must be rejected');

$source = '/tmp/a name;$(unsafe).webm';
$output = '/tmp/result.mp4.part';
$command = $transcoder->command($source, $output, true);
videoExpectSame('/usr/bin/ffmpeg', $command[0], 'ffmpeg binary must be an argument, not shell text');
videoExpectSame($source, $command[array_search('-i', $command, true) + 1], 'source path must remain one literal argument');
videoExpectSame(['-map','0:v:0','-map','0:a?'], array_slice($command, array_search('-map', $command, true), 4), 'audio mapping must be optional');
videoExpectSame($output, $command[array_key_last($command)], 'output path must remain one literal argument');
videoExpectSame(false, in_array('sh', $command, true), 'command must not invoke a shell');

videoExpectSame(25, $transcoder->progress("out_time_us=2500000\nprogress=continue\n", 10.0, 0), 'progress must use media time');
videoExpectSame(25, $transcoder->progress("out_time_us=1000000\n", 10.0, 25), 'progress must never decrease');
videoExpectSame(99, $transcoder->progress("out_time_us=999999999\n", 10.0, 25), 'running progress must be capped below completion');
videoExpectSame(100, $transcoder->progress("progress=end\n", 10.0, 99), 'end marker must complete progress');

echo "PASS: video transcoder contract\n";
