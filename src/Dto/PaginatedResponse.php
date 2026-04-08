<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;



/**
 * @template T
 */
class PaginatedResponse extends Data
{
    /**
     * @param T[] $data
     */
    public function __construct(
        public readonly array $data,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
        public readonly ?string $firstPageUrl,
        public readonly ?string $lastPageUrl,
        public readonly ?string $nextPageUrl,
        public readonly ?string $prevPageUrl,
    ) {}

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }
}
