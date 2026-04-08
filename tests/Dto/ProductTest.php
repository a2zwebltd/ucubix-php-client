<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\Product;

class ProductTest extends TestCase
{
    public function test_constructor_and_properties(): void
    {
        $product = new Product(
            id: 'prod-uuid-123',
            name: 'Test Game',
            summary: 'A short summary',
            description: 'A test game description',
            release_date: '2024-03-15T00:00:00.000000Z',
            type: 'Game',
            created_at: '2024-01-01T00:00:00.000000Z',
            regional_pricing: [],
            metadata: null,
        );

        $this->assertEquals('prod-uuid-123', $product->id);
        $this->assertEquals('Test Game', $product->name);
        $this->assertEquals('A short summary', $product->summary);
        $this->assertEquals('Game', $product->type);
        $this->assertEmpty($product->regional_pricing);
        $this->assertNull($product->metadata);
    }
}
