<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;



class ProductMetadata extends Data
{
    /**
     * @param SystemRequirement[] $minimum
     * @param SystemRequirement[] $recommended
     */
    public function __construct(
        public readonly array $minimum,
        public readonly array $recommended,
        public readonly ?SteamdbInfo $steamdb,
    ) {}
}
