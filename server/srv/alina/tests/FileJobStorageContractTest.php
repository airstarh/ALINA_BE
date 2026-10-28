<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/FileJob/FileJobStorage.php';
require_once __DIR__ . '/../Services/FileJob/DatabaseFileJobStorage.php';

use alina\Services\FileJob\DatabaseFileJobStorage;

function fileJobStorageExpect(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$row = (object) [
    'id' => 9,
    'public_id' => '123e4567-e89b-42d3-a456-426614174000',
    'type' => 'video_transcode',
    'status' => 'processing',
    'owner_id' => 17,
    'source_tmp' => '/private/source.webm',
    'result_tmp' => '/private/result.mp4.part',
    'payload' => json_encode(['wake_token_hash' => hash('sha256', 'wake-secret'), 'private' => 'value']),
    'progress' => 41,
    'error' => 'private ffmpeg diagnostic',
    'created_at' => 100,
    'started_at' => 110,
    'finished_at' => null,
    'updated_at' => 120,
];

$snapshot = DatabaseFileJobStorage::safeSnapshotFromRow($row);
fileJobStorageExpect($snapshot === [
    'publicId' => '123e4567-e89b-42d3-a456-426614174000',
    'type' => 'video_transcode',
    'status' => 'processing',
    'progress' => 41,
    'error' => null,
    'createdAt' => 100,
    'startedAt' => 110,
    'finishedAt' => null,
    'updatedAt' => 120,
], 'safe snapshot must expose only the public job contract');
fileJobStorageExpect(DatabaseFileJobStorage::wakeSecretMatchesRow($row, 'wake-secret'), 'correct wake secret must match');
fileJobStorageExpect(! DatabaseFileJobStorage::wakeSecretMatchesRow($row, 'wrong-secret'), 'wrong wake secret must be rejected');
fileJobStorageExpect(! DatabaseFileJobStorage::wakeSecretMatchesRow((object) ['payload' => '{}'], 'wake-secret'), 'missing wake hash must be rejected');

$failed = clone $row;
$failed->status = 'failed';
$failed->error = 'Video conversion failed.';
fileJobStorageExpect(DatabaseFileJobStorage::safeSnapshotFromRow($failed)['error'] === 'Video conversion failed.', 'failed jobs may expose their public error');

echo "PASS: file job storage contract\n";
