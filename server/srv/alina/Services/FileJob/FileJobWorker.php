<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use Closure;
use React\EventLoop\LoopInterface;
use Throwable;

final class FileJobWorker
{
    private const LEASE_SECONDS = 60;
    private const MAX_ATTEMPTS = 2;
    private bool $busy = false;
    private Closure $publish;
    private Closure $finalize;
    private Closure $now;

    public function __construct(
        private readonly FileJobStorage $storage,
        private readonly FileJobProcessor $processor,
        private readonly LoopInterface $loop,
        callable $publish,
        callable $finalize,
        ?callable $now = null,
        private readonly string $workerId = '',
    ) {
        $this->publish = Closure::fromCallable($publish);
        $this->finalize = Closure::fromCallable($finalize);
        $this->now = Closure::fromCallable($now ?? static fn (): int => time());
    }

    public function wake(string $publicId, string $secret): bool
    {
        if (! $this->storage->wakeSecretMatches($publicId, $secret)) {
            return false;
        }

        if (! $this->busy) {
            $job = $this->storage->claim($publicId, $this->id(), ($this->now)(), self::LEASE_SECONDS);
            if ($job !== null) $this->process($job);
        }

        return true;
    }

    public function recover(): void
    {
        if ($this->busy) return;
        $job = $this->storage->claimNext($this->id(), ($this->now)(), self::LEASE_SECONDS);
        if ($job !== null) $this->process($job);
    }

    public function enableRecoveryTimer(float $seconds = 30.0): void
    {
        $this->loop->addPeriodicTimer($seconds, fn () => $this->recover());
    }

    private function process(object $job): void
    {
        $this->busy = true;
        $publicId = (string) $job->public_id;
        ($this->publish)($publicId);

        try {
            $this->processor->start(
                $job,
                function (int $progress) use ($job, $publicId): void {
                    $this->storage->updateProgress((int) $job->id, $this->id(), $progress, ($this->now)(), self::LEASE_SECONDS);
                    ($this->publish)($publicId);
                },
                function () use ($job, $publicId): void {
                    try {
                        ($this->finalize)($job);
                        $this->storage->complete((int) $job->id, $this->id(), ($this->now)());
                        ($this->publish)($publicId);
                        $this->finished();
                    }
                    catch (Throwable $error) {
                        $this->failed($job, $publicId, $error);
                    }
                },
                fn (string $diagnostic) => $this->failed($job, $publicId, new \RuntimeException($diagnostic)),
            );
        }
        catch (Throwable $error) {
            $this->failed($job, $publicId, $error);
        }
    }

    private function failed(object $job, string $publicId, Throwable $error): void
    {
        $this->storage->retryOrFail(
            (int) $job->id,
            $this->id(),
            'Video conversion failed.',
            $error->getMessage(),
            ($this->now)(),
            self::MAX_ATTEMPTS,
        );
        ($this->publish)($publicId);
        $this->finished();
    }

    private function finished(): void
    {
        $this->busy = false;
        $this->loop->futureTick(fn () => $this->recover());
    }

    private function id(): string
    {
        return $this->workerId !== '' ? $this->workerId : gethostname() . ':' . getmypid();
    }
}
