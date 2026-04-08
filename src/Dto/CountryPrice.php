<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class CountryPrice extends Data
{
    public function __construct(
        public readonly string $country_name,
        public readonly string $country_code,
        public readonly ?float $price,
        public readonly ?string $currency_code,
        public readonly bool $is_promotion,
        public readonly ?float $original_price,
        public readonly ?string $promotion_name,
        public readonly ?string $promotion_end_date,
        public readonly bool $can_be_ordered,
        public readonly bool $in_stock,
    ) {}
}
