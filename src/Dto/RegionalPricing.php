<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class RegionalPricing extends Data
{
    /**
     * @param CountryPrice[] $countries
     */
    public function __construct(
        public readonly string $region_code,
        public readonly float $reseller_wsp,
        public readonly array $countries,
    ) {}
}
