<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use Illuminate\Database\Capsule\Manager as Dal;
use stdClass;

final class DatabaseFileJobStorage implements FileJobStorage
{
    public function authorizedSnapshot(string $publicId, int $userId, string $token, int $now): ?array
    {
        if ($userId <= 0 || strlen($token) < 10) {
            return null;
        }

        $authorized = Dal::table('login')
            ->where('user_id', '=', $userId)
            ->where('token', '=', $token)
            ->where('expires_at', '>', $now)
            ->exists();

        if (! $authorized) {
            return null;
        }

        $row = Dal::table('file_job')
            ->where('public_id', '=', $publicId)
            ->where('owner_id', '=', $userId)
            ->first();

        return $row === null ? null : self::safeSnapshotFromRow($row);
    }

    public function snapshot(string $publicId): ?array
    {
        $row = Dal::table('file_job')->where('public_id', '=', $publicId)->first();

        return $row === null ? null : self::safeSnapshotFromRow($row);
    }

    public function wakeSecretMatches(string $publicId, string $secret): bool
    {
        if ($secret === '' || strlen($secret) > 512) {
            return false;
        }

        $row = Dal::table('file_job')->where('public_id', '=', $publicId)->first(['payload']);

        return $row !== null && self::wakeSecretMatchesRow($row, $secret);
    }

    public function claim(string $publicId, string $workerId, int $now, int $leaseSeconds): ?object
    {
        return Dal::connection()->transaction(function () use ($publicId, $workerId, $now, $leaseSeconds): ?object {
            $row = Dal::table('file_job')->where('public_id', '=', $publicId)->lockForUpdate()->first();

            if ($row === null || ! $this->isClaimable($row, $now)) {
                return null;
            }

            Dal::table('file_job')->where('id', '=', $row->id)->update([
                'status' => 'processing',
                'worker_id' => $workerId,
                'lease_until' => $now + $leaseSeconds,
                'started_at' => $row->started_at ?? $now,
                'updated_at' => $now,
                'attempts' => ((int) $row->attempts) + 1,
                'error' => null,
            ]);

            return Dal::table('file_job')->where('id', '=', $row->id)->first();
        });
    }

    public function claimNext(string $workerId, int $now, int $leaseSeconds): ?object
    {
        $candidate = Dal::table('file_job')
            ->where(function ($query) use ($now): void {
                $query->where('status', '=', 'queued')
                    ->orWhere(function ($expired) use ($now): void {
                        $expired->where('status', '=', 'processing')->where('lease_until', '<', $now);
                    });
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->first(['public_id']);

        return $candidate === null ? null : $this->claim((string) $candidate->public_id, $workerId, $now, $leaseSeconds);
    }

    public function updateProgress(int $jobId, string $workerId, int $progress, int $now, int $leaseSeconds): bool
    {
        $progress = max(0, min(100, $progress));

        return Dal::table('file_job')
            ->where('id', '=', $jobId)
            ->where('status', '=', 'processing')
            ->where('worker_id', '=', $workerId)
            ->update([
                'progress' => Dal::raw('GREATEST(`progress`, ' . $progress . ')'),
                'lease_until' => $now + $leaseSeconds,
                'updated_at' => $now,
            ]) === 1;
    }

    public function complete(int $jobId, string $workerId, int $now): bool
    {
        return Dal::table('file_job')
            ->where('id', '=', $jobId)
            ->where('status', '=', 'processing')
            ->where('worker_id', '=', $workerId)
            ->update([
                'status' => 'completed',
                'progress' => 100,
                'lease_until' => null,
                'worker_id' => null,
                'finished_at' => $now,
                'updated_at' => $now,
            ]) === 1;
    }

    public function retryOrFail(int $jobId, string $workerId, string $publicError, string $privateError, int $now, int $maxAttempts): string
    {
        return Dal::connection()->transaction(function () use ($jobId, $workerId, $publicError, $privateError, $now, $maxAttempts): string {
            $row = Dal::table('file_job')->where('id', '=', $jobId)->where('worker_id', '=', $workerId)->lockForUpdate()->first();
            if ($row === null || $row->status !== 'processing') {
                return 'ignored';
            }

            $retry = (int) $row->attempts < $maxAttempts;
            $status = $retry ? 'queued' : 'failed';
            $payload = self::payload($row);
            $payload['private_error'] = mb_substr($privateError, 0, 8000);

            Dal::table('file_job')->where('id', '=', $jobId)->update([
                'status' => $status,
                'error' => $retry ? null : mb_substr($publicError, 0, 2000),
                'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'worker_id' => null,
                'lease_until' => null,
                'finished_at' => $retry ? null : $now,
                'updated_at' => $now,
            ]);

            return $status;
        });
    }

    public static function safeSnapshotFromRow(object $row): array
    {
        return [
            'publicId' => (string) $row->public_id,
            'type' => (string) $row->type,
            'status' => (string) $row->status,
            'progress' => max(0, min(100, (int) $row->progress)),
            'error' => $row->status === 'failed' ? ((string) ($row->error ?? '') ?: 'File processing failed.') : null,
            'createdAt' => (int) $row->created_at,
            'startedAt' => $row->started_at === null ? null : (int) $row->started_at,
            'finishedAt' => $row->finished_at === null ? null : (int) $row->finished_at,
            'updatedAt' => (int) $row->updated_at,
        ];
    }

    public static function wakeSecretMatchesRow(object $row, string $secret): bool
    {
        $hash = self::payload($row)['wake_token_hash'] ?? null;

        return is_string($hash) && strlen($hash) === 64 && hash_equals($hash, hash('sha256', $secret));
    }

    private function isClaimable(object $row, int $now): bool
    {
        return $row->status === 'queued'
            || ($row->status === 'processing' && $row->lease_until !== null && (int) $row->lease_until < $now);
    }

    private static function payload(object $row): array
    {
        $payload = $row->payload ?? null;
        if (is_array($payload)) {
            return $payload;
        }
        if (! is_string($payload) || $payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }
}
