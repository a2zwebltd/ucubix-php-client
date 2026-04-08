<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class SteamdbInfo extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $url,
    ) {}
}
