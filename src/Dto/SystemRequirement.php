<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class SystemRequirement extends Data
{
    public function __construct(
        public readonly ?string $parameter,
        public readonly ?string $value,
    ) {}
}
