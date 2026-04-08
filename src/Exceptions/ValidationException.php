<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Exceptions;

class ValidationException extends ApiException
{
    public function __construct(
        string $message = 'Validation failed',
        public readonly ?string $field = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 422, $previous, $field);
    }
}
