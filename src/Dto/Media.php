<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class Media extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $file_name,
        public readonly string $collection_name,
        public readonly string $mime_type,
        public readonly string $disk,
        public readonly int $size,
        public readonly ?int $order_column,
        public readonly string $url,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}
}
