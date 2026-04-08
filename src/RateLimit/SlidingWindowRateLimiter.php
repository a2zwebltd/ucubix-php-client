<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\RateLimit;

class SlidingWindowRateLimiter
{
    /** @var float[] Timestamps of recent requests */
    private array $timestamps = [];

    public function __construct(
        private int $maxRequests = 100,
        private int $windowSeconds = 60,
    ) {}

    /**
     * Block until a request slot is available.
     * Returns the number of seconds waited (0.0 if no wait was needed).
     */
    public function waitIfNeeded(): float
    {
        $this->pruneExpired();

        $waited = 0.0;

        if (count($this->timestamps) >= $this->maxRequests) {
            $oldestInWindow = $this->timestamps[0];
            $waitUntil = $oldestInWindow + $this->windowSeconds;
            $sleepTime = $waitUntil - microtime(true) + 0.05; // 50ms buffer

            if ($sleepTime > 0) {
                usleep((int) ($sleepTime * 1_000_000));
                $waited = $sleepTime;
            }

            $this->pruneExpired();
        }

        $this->timestamps[] = microtime(true);

        return $waited;
    }

    /**
     * Non-blocking check: can we send a request right now?
     */
    public function canProceed(): bool
    {
        $this->pruneExpired();

        return count($this->timestamps) < $this->maxRequests;
    }

    /**
     * How many requests can still be made in the current window.
     */
    public function remaining(): int
    {
        $this->pruneExpired();

        return max(0, $this->maxRequests - count($this->timestamps));
    }

    /**
     * Adapt from server-reported limit (one-way ratchet — only goes up).
     */
    public function adaptFromServerLimit(int $serverLimit): void
    {
        if ($serverLimit > $this->maxRequests) {
            $this->maxRequests = $serverLimit;
        }
    }

    public function getMaxRequests(): int
    {
        return $this->maxRequests;
    }

    public function setMaxRequests(int $maxRequests): void
    {
        $this->maxRequests = max(1, $maxRequests);
    }

    public function getWindowSeconds(): int
    {
        return $this->windowSeconds;
    }

    public function reset(): void
    {
        $this->timestamps = [];
    }

    private function pruneExpired(): void
    {
        $cutoff = microtime(true) - $this->windowSeconds;
        $this->timestamps = array_values(
            array_filter($this->timestamps, fn(float $ts) => $ts > $cutoff)
        );
    }
}
