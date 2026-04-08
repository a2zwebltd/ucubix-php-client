<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

class Category extends Data
{
    /**
     * @param string[] $child_ids
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $parent_id,
        public readonly array $child_ids,
    ) {}
}
