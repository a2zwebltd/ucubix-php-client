<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\RateLimit\SlidingWindowRateLimiter;

class RateLimitTest extends TestCase
{
    public function test_default_configuration(): void
    {
        $limiter = new SlidingWindowRateLimiter();

        $this->assertEquals(100, $limiter->getMaxRequests());
        $this->assertEquals(60, $limiter->getWindowSeconds());
    }

    public function test_custom_configuration(): void
    {
        $limiter = new SlidingWindowRateLimiter(maxRequests: 50, windowSeconds: 30);

        $this->assertEquals(50, $limiter->getMaxRequests());
        $this->assertEquals(30, $limiter->getWindowSeconds());
    }

    public function test_adapt_from_server_limit_increases(): void
    {
        $limiter = new SlidingWindowRateLimiter(maxRequests: 100);

        $limiter->adaptFromServerLimit(200);
        $this->assertEquals(200, $limiter->getMaxRequests());
    }

    public function test_adapt_from_server_limit_does_not_decrease(): void
    {
        $limiter = new SlidingWindowRateLimiter(maxRequests: 100);

        $limiter->adaptFromServerLimit(50);
        $this->assertEquals(100, $limiter->getMaxRequests());
    }

    public function test_wait_if_needed_does_not_block_under_limit(): void
    {
        $limiter = new SlidingWindowRateLimiter(maxRequests: 100, windowSeconds: 60);

        $start = microtime(true);
        for ($i = 0; $i < 5; $i++) {
            $limiter->waitIfNeeded();
        }
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.1, $elapsed, 'Should not block when under the limit');
    }

    public function test_reset(): void
    {
        $limiter = new SlidingWindowRateLimiter(maxRequests: 5, windowSeconds: 60);

        for ($i = 0; $i < 5; $i++) {
            $limiter->waitIfNeeded();
        }

        $limiter->reset();

        $start = microtime(true);
        $limiter->waitIfNeeded();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.1, $elapsed, 'Should not block after reset');
    }
}
