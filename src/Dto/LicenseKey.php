<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class LicenseKey extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $license_key,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}
}
