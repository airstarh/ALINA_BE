<?php

declare(strict_types=1);

require_once __DIR__ . '/../mvc/Controller/Tale.php';

use alina\mvc\Controller\Tale;

function talePinExpectSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function expectInvalidPinTarget(object $comment, string $message): void
{
    try {
        Tale::pinnedCommentRootId($comment);
    }
    catch (InvalidArgumentException $e) {
        return;
    }

    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

$firstLevelComment = (object)[
    'id' => 41,
    'type' => 'COMMENT',
    'level' => 1,
    'root_tale_id' => 7,
    'answer_to_tale_id' => 7,
];

talePinExpectSame(7, Tale::pinnedCommentRootId($firstLevelComment), 'a first-level comment must resolve to its root tale');

expectInvalidPinTarget((object)[
    'id' => 42,
    'type' => 'COMMENT',
    'level' => 2,
    'root_tale_id' => 7,
    'answer_to_tale_id' => 41,
], 'a nested comment must not be accepted as a pin target');

expectInvalidPinTarget((object)[
    'id' => 43,
    'type' => 'COMMENT',
    'level' => 1,
    'root_tale_id' => 7,
    'answer_to_tale_id' => 41,
], 'a level-one row whose direct parent is not the root tale must not be accepted');

expectInvalidPinTarget((object)[
    'id' => 7,
    'type' => 'POST',
    'level' => 0,
    'root_tale_id' => null,
    'answer_to_tale_id' => null,
], 'a tale post must not be accepted as a pin target');

echo "PASS: pinned-comment target rules\n";
