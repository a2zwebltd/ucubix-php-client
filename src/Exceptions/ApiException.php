<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Exceptions;

class ApiException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?string $errorKey = null,
        public readonly ?string $errorDetail = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
