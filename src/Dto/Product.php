<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class Product extends Data
{
    /**
     * @param RegionalPricing[] $regional_pricing Only present on single-resource requests
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $summary,
        public readonly ?string $description,
        public readonly ?string $release_date,
        public readonly ?string $type,
        public readonly ?string $created_at,
        public readonly array $regional_pricing,
        public readonly ?ProductMetadata $metadata,
    ) {}
}
