<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;



class OrganisationSummary extends Data
{
    public function __construct(
        public readonly int $currencies,
        public readonly string $total_usd_equivalent,
    ) {}
}
