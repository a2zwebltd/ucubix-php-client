<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Exceptions;

class RateLimitException extends ApiException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        public readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $previous);
    }
}
