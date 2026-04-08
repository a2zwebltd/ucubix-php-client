<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;



class Organisation extends Data
{
    /**
     * @param CreditLine[] $credit_lines
     */
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
        public readonly OrganisationSummary $summary,
        public readonly array $credit_lines,
    ) {}
}
