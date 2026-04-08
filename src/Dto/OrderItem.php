<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class OrderItem extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly float $price,
        public readonly ?string $country_code,
        public readonly ?string $license_key_uuid,
        public readonly ?string $fulfilled_at,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}

    public function hasLicenseKey(): bool
    {
        return $this->license_key_uuid !== null;
    }
}
