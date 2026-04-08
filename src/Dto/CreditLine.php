<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;



class CreditLine extends Data
{
    public function __construct(
        public readonly string $currency,
        public readonly string $balance,
    ) {}
}
