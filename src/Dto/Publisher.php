<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class Publisher extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $website,
        public readonly ?string $about,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}
}
