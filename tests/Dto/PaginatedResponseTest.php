<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\PaginatedResponse;
use Ucubix\PhpClient\Dto\Franchise;

class PaginatedResponseTest extends TestCase
{
    public function test_constructor_and_properties(): void
    {
        $items = [
            new Franchise(id: 'f1', name: 'Franchise 1', created_at: '2024-01-01T00:00:00Z'),
            new Franchise(id: 'f2', name: 'Franchise 2', created_at: '2024-01-01T00:00:00Z'),
        ];

        $paginated = new PaginatedResponse(
            data: $items,
            currentPage: 1,
            perPage: 15,
            total: 50,
            lastPage: 4,
            firstPageUrl: 'http://localhost/api/v1/franchises?page[number]=1',
            lastPageUrl: 'http://localhost/api/v1/franchises?page[number]=4',
            nextPageUrl: 'http://localhost/api/v1/franchises?page[number]=2',
            prevPageUrl: null,
        );

        $this->assertCount(2, $paginated->data);
        $this->assertEquals(1, $paginated->currentPage);
        $this->assertEquals(50, $paginated->total);
        $this->assertEquals(4, $paginated->lastPage);
        $this->assertTrue($paginated->hasMorePages());
        $this->assertNull($paginated->prevPageUrl);
    }

    public function test_last_page_has_no_more_pages(): void
    {
        $paginated = new PaginatedResponse(
            data: [],
            currentPage: 4,
            perPage: 15,
            total: 50,
            lastPage: 4,
            firstPageUrl: 'http://localhost/page1',
            lastPageUrl: 'http://localhost/page4',
            nextPageUrl: null,
            prevPageUrl: 'http://localhost/page3',
        );

        $this->assertFalse($paginated->hasMorePages());
    }
}
