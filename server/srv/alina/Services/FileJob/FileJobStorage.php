<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

interface FileJobStorage
{
    public function authorizedSnapshot(string $publicId, int $userId, string $token, int $now): ?array;

    public function snapshot(string $publicId): ?array;

    public function wakeSecretMatches(string $publicId, string $secret): bool;

    public function claim(string $publicId, string $workerId, int $now, int $leaseSeconds): ?object;

    public function claimNext(string $workerId, int $now, int $leaseSeconds): ?object;

    public function updateProgress(int $jobId, string $workerId, int $progress, int $now, int $leaseSeconds): bool;

    public function complete(int $jobId, string $workerId, int $now): bool;

    public function retryOrFail(int $jobId, string $workerId, string $publicError, string $privateError, int $now, int $maxAttempts): string;
}
